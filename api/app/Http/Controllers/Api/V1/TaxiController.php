<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaxiCategory;
use App\Models\TaxiTrip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxiController extends Controller
{
    /**
     * GET /v1/taxi/categories
     * List all active taxi categories with fare rates.
     */
    public function categories(): JsonResponse
    {
        $categories = TaxiCategory::where('is_active', true)->orderBy('sort_order')->get();
        return response()->json(['success' => true, 'data' => $categories]);
    }

    /**
     * GET /v1/taxi/estimate
     * Estimate fare for a proposed trip.
     *
     * Query params: category_id, distance_km
     */
    public function estimate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:taxi_categories,id'],
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:500'],
        ]);

        $category = TaxiCategory::findOrFail($data['category_id']);
        $distance = (float) $data['distance_km'];

        $base     = (float) $category->base_fare;
        $perKm    = (float) $category->per_km_rate;
        $estimate = $base + ($distance * $perKm);
        $estimate = max($estimate, $base);

        return response()->json([
            'success'       => true,
            'category'      => $category->name,
            'distance_km'   => $distance,
            'estimated_fare' => round($estimate, 2),
            'currency'      => 'LKR',
        ]);
    }

    /**
     * POST /v1/taxi/rides
     * Customer requests a new ride.
     */
    public function requestRide(Request $request): JsonResponse
    {
        $data = $request->validate([
            'taxi_category_id'  => ['required', 'integer', 'exists:taxi_categories,id'],
            'origin_lat'        => ['required', 'numeric', 'between:-90,90'],
            'origin_lng'        => ['required', 'numeric', 'between:-180,180'],
            'origin_address'    => ['required', 'string', 'max:255'],
            'dest_lat'          => ['nullable', 'numeric', 'between:-90,90'],
            'dest_lng'          => ['nullable', 'numeric', 'between:-180,180'],
            'dest_address'      => ['nullable', 'string', 'max:255'],
            'stops'             => ['nullable', 'array', 'max:5'],
            'payment_method'    => ['required', 'in:card,cash'],
        ]);

        $category = TaxiCategory::findOrFail($data['taxi_category_id']);

        // Check customer has no active ride
        $existingActive = TaxiTrip::where('customer_id', $request->user()->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($existingActive) {
            abort(422, 'You already have an active ride.');
        }

        $trip = TaxiTrip::create([
            ...$data,
            'customer_id'  => $request->user()->id,
            'status'       => 'searching',
        ]);

        return response()->json([
            'success' => true,
            'trip'    => $trip,
            'message' => 'Ride requested. Looking for a driver.',
        ], 201);
    }

    /**
     * GET /v1/taxi/active
     * Customer or driver views their current active ride.
     */
    public function activeRide(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $trip = TaxiTrip::with(['taxiCategory', 'driver.profile', 'customer.profile'])
            ->where(function ($q) use ($userId) {
                $q->where('customer_id', $userId)->orWhere('driver_id', $userId);
            })
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest()
            ->first();

        if (!$trip) {
            return response()->json(['success' => true, 'trip' => null]);
        }

        return response()->json(['success' => true, 'trip' => $trip]);
    }

    /**
     * PATCH /v1/taxi/rides/{id}/status
     * Driver (or system) advances trip status.
     *
     * Allowed transitions:
     *   searching → accepted (driver accepts)
     *   accepted  → driver_arrived
     *   driver_arrived → in_transit (trip started)
     *   in_transit → completed
     *   any non-terminal → cancelled
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status'        => ['required', 'in:accepted,driver_arrived,in_transit,completed,cancelled'],
            'cancel_reason' => ['nullable', 'string', 'max:255'],
            'final_fare'    => ['nullable', 'numeric', 'min:0'],
            'distance_km'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $trip = TaxiTrip::findOrFail($id);

        // Only driver or customer may update
        $userId = $request->user()->id;
        abort_unless(
            $trip->driver_id === $userId || $trip->customer_id === $userId,
            403,
            'Forbidden.'
        );

        $now    = now();
        $update = ['status' => $data['status']];

        match ($data['status']) {
            'accepted'       => $update['accepted_at'] = $now,
            'driver_arrived' => $update['arrived_at'] = $now,
            'in_transit'     => $update['started_at'] = $now,
            'completed'      => $update = array_merge($update, [
                'completed_at' => $now,
                'final_fare'   => $data['final_fare'] ?? $trip->estimated_fare,
                'distance_km'  => $data['distance_km'] ?? $trip->distance_km,
            ]),
            'cancelled'      => $update = array_merge($update, [
                'cancelled_at' => $now,
                'cancel_reason' => $data['cancel_reason'] ?? null,
            ]),
            default          => null,
        };

        // Assign driver on accept
        if ($data['status'] === 'accepted') {
            $update['driver_id'] = $userId;
        }

        $trip->update($update);

        return response()->json(['success' => true, 'trip' => $trip->fresh()]);
    }

    /**
     * GET /v1/taxi/rides
     * Customer/driver history.
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
}
