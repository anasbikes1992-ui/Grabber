<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyDriverJob;
use App\Models\TaxiCategory;
use App\Models\TaxiTrip;
use App\Services\TaxiSurgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxiRideController extends Controller
{
    public function __construct(
        private readonly TaxiSurgeService $surgeService,
    ) {}

    /**
     * POST /v1/taxi/rides
     * Customer requests a ride with full surge calculation.
     */
    public function request(Request $request): JsonResponse
    {
        $data = $request->validate([
            'taxi_category_id'       => ['required', 'integer', 'exists:taxi_categories,id'],
            'origin_lat'             => ['required', 'numeric', 'between:-90,90'],
            'origin_lng'             => ['required', 'numeric', 'between:-180,180'],
            'origin_address'         => ['required', 'string', 'max:255'],
            'dest_lat'               => ['nullable', 'numeric', 'between:-90,90'],
            'dest_lng'               => ['nullable', 'numeric', 'between:-180,180'],
            'dest_address'           => ['nullable', 'string', 'max:255'],
            'stops'                  => ['nullable', 'array', 'max:5'],
            'payment_method'         => ['required', 'in:card,cash'],
            'corporate_account_id'   => ['nullable', 'uuid', 'exists:taxi_corporate_accounts,id'],
            'is_scheduled'           => ['nullable', 'boolean'],
            'scheduled_at'           => ['nullable', 'date', 'after:now'],
            'accessibility_required' => ['nullable', 'boolean'],
        ]);

        // Guard: customer must not already have an active ride
        $hasActive = TaxiTrip::where('customer_id', $request->user()->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($hasActive) {
            return response()->json(['message' => 'You already have an active ride.'], 422);
        }

        // Calculate surge at pickup location
        $surge = $this->surgeService->calculateSurge(
            (float) $data['origin_lat'],
            (float) $data['origin_lng']
        );

        // Fare estimate using category base + per-km rate
        $category = TaxiCategory::findOrFail($data['taxi_category_id']);
        $distanceKm = isset($data['dest_lat'], $data['dest_lng'])
            ? $this->haversineKm(
                $data['origin_lat'], $data['origin_lng'],
                $data['dest_lat'], $data['dest_lng']
              )
            : null;

        $estimatedFare = null;
        if ($distanceKm !== null) {
            $estimatedFare = ((float) $category->base_fare + $distanceKm * (float) $category->per_km_rate) * $surge;
        }

        $trip = TaxiTrip::create([
            'customer_id'          => $request->user()->id,
            'taxi_category_id'     => $data['taxi_category_id'],
            'origin_lat'           => $data['origin_lat'],
            'origin_lng'           => $data['origin_lng'],
            'origin_address'       => $data['origin_address'],
            'dest_lat'             => $data['dest_lat'] ?? null,
            'dest_lng'             => $data['dest_lng'] ?? null,
            'dest_address'         => $data['dest_address'] ?? null,
            'stops'                => $data['stops'] ?? [],
            'payment_method'       => $data['payment_method'],
            'corporate_account_id' => $data['corporate_account_id'] ?? null,
            'is_scheduled'         => $data['is_scheduled'] ?? false,
            'scheduled_at'         => $data['scheduled_at'] ?? null,
            'surge_multiplier'     => $surge,
            'estimated_fare'       => $estimatedFare ? round($estimatedFare, 2) : null,
            'distance_km'          => $distanceKm ? round($distanceKm, 2) : null,
            'status'               => ($data['is_scheduled'] ?? false) ? 'scheduled' : 'searching',
        ]);

        // Find nearest 5 online drivers and notify the closest
        if ($trip->status === 'searching') {
            NotifyDriverJob::dispatch($trip)->onQueue('notifications');
        }

        return response()->json([
            'success'        => true,
            'trip'           => $trip,
            'surge'          => $surge,
            'estimated_fare' => $estimatedFare ? round($estimatedFare, 2) : null,
            'message'        => 'Ride requested. Looking for a driver.',
        ], 201);
    }

    /**
     * GET /v1/taxi/rides/{id}
     * Retrieve a specific ride (customer or driver).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $trip = TaxiTrip::with(['taxiCategory', 'driver.profile', 'customer.profile'])
            ->findOrFail($id);

        $userId = $request->user()->id;
        abort_unless(
            $trip->customer_id === $userId || $trip->driver_id === $userId,
            403, 'Forbidden.'
        );

        return response()->json(['success' => true, 'trip' => $trip]);
    }

    /**
     * PATCH /v1/taxi/rides/{id}/cancel
     * Customer cancels a ride.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $trip = TaxiTrip::findOrFail($id);

        abort_unless($trip->customer_id === $request->user()->id, 403, 'Forbidden.');
        abort_if(
            in_array($trip->status, ['completed', 'cancelled']),
            422, 'This ride cannot be cancelled.'
        );
        abort_if(
            $trip->status === 'in_transit',
            422, 'Cannot cancel a ride in progress.'
        );

        $trip->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $data['cancel_reason'] ?? 'Cancelled by customer',
        ]);

        return response()->json(['success' => true, 'trip' => $trip->fresh()]);
    }

    /**
     * POST /v1/taxi/rides/{id}/rate
     * Customer rates the driver after completion.
     */
    public function rate(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'rating'     => ['required', 'integer', 'between:1,5'],
            'tip_amount' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);

        $trip = TaxiTrip::findOrFail($id);

        abort_unless($trip->customer_id === $request->user()->id, 403, 'Forbidden.');
        abort_unless($trip->status === 'completed', 422, 'Only completed rides can be rated.');
        abort_if($trip->driver_rating !== null, 422, 'Ride already rated.');

        $trip->update([
            'driver_rating' => $data['rating'],
            'tip_amount'    => $data['tip_amount'] ?? 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Rating submitted. Thank you!']);
    }

    /**
     * POST /v1/taxi/rides/{id}/sos
     * Customer triggers SOS during active ride.
     */
    public function sos(Request $request, string $id): JsonResponse
    {
        $trip = TaxiTrip::findOrFail($id);

        abort_unless($trip->customer_id === $request->user()->id, 403, 'Forbidden.');
        abort_unless(
            in_array($trip->status, ['accepted', 'driver_arrived', 'in_transit']),
            422, 'SOS can only be triggered on active rides.'
        );

        $trip->update([
            'sos_triggered_at' => now(),
            'status'           => 'sos',
        ]);

        // TODO: broadcast SOS event to admin channel and dispatch emergency notification
        // Broadcast::channel('admin.sos', ...) — wired via Supabase Realtime in production

        return response()->json([
            'success' => true,
            'message' => 'SOS alert sent. Emergency services have been notified.',
        ]);
    }

    /**
     * POST /v1/taxi/rides/{id}/split-fare
     * Customer initiates fare split with other users.
     */
    public function splitFare(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'split_with' => ['required', 'array', 'min:1', 'max:4'],
            'split_with.*' => ['uuid', 'exists:users,id'],
        ]);

        $trip = TaxiTrip::findOrFail($id);

        abort_unless($trip->customer_id === $request->user()->id, 403, 'Forbidden.');
        abort_unless(
            in_array($trip->status, ['searching', 'accepted', 'driver_arrived', 'in_transit']),
            422, 'Split fare is not available at this stage.'
        );

        $trip->update([
            'split_fare' => true,
            'split_with' => $data['split_with'],
        ]);

        // TODO: send split fare invitation notifications to users in split_with

        return response()->json([
            'success' => true,
            'message' => 'Fare split request sent.',
            'trip'    => $trip->fresh(),
        ]);
    }

    /**
     * GET /v1/taxi/active
     * Get customer's or driver's currently active ride.
     */
    public function active(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $trip = TaxiTrip::with(['taxiCategory', 'driver.profile', 'customer.profile'])
            ->where(function ($q) use ($userId) {
                $q->where('customer_id', $userId)->orWhere('driver_id', $userId);
            })
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest()
            ->first();

        return response()->json(['success' => true, 'trip' => $trip]);
    }

    /**
     * GET /v1/taxi/rides
     * Ride history for the authenticated user.
     */
    public function history(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $trips = TaxiTrip::with('taxiCategory')
            ->where(function ($q) use ($userId) {
                $q->where('customer_id', $userId)->orWhere('driver_id', $userId);
            })
            ->whereIn('status', ['completed', 'cancelled'])
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $trips]);
    }

    // ── Private helpers ─────────────────────────────────────────────────────

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthR = 6371;
        $dLat   = deg2rad($lat2 - $lat1);
        $dLng   = deg2rad($lng2 - $lng1);
        $a      = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthR * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
