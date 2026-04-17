<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProviderWallet;
use App\Models\TaxiCashCommissionInvoice;
use App\Models\TaxiTrip;
use App\Services\CashCommissionInvoiceService;
use App\Services\TaxiQuestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TaxiDriverController extends Controller
{
    public function __construct(
        private readonly TaxiQuestService $questService,
        private readonly CashCommissionInvoiceService $commissionService,
    ) {}

    /**
     * POST /v1/taxi/driver/status
     * Toggle driver online / offline status.
     */
    public function setStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_online' => ['required', 'boolean'],
        ]);

        $request->user()->update([
            'is_online' => $data['is_online'],
        ]);

        return response()->json([
            'success'   => true,
            'is_online' => $data['is_online'],
        ]);
    }

    /**
     * POST /v1/taxi/driver/location
     * Push driver location during active ride.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat'  => ['required', 'numeric', 'between:-90,90'],
            'lng'  => ['required', 'numeric', 'between:-180,180'],
        ]);

        $request->user()->update([
            'current_lat' => $data['lat'],
            'current_lng' => $data['lng'],
        ]);

        $activeTrip = TaxiTrip::where('driver_id', $request->user()->id)
            ->whereIn('status', ['accepted', 'driver_arrived', 'in_transit'])
            ->first();

        if ($activeTrip) {
            // Push location to Supabase Realtime for customer tracking
            $supabaseUrl = config('services.supabase.url');
            $supabaseKey = config('services.supabase.service_key');

            if ($supabaseUrl && $supabaseKey) {
                Http::withHeaders([
                    'apikey'        => $supabaseKey,
                    'Authorization' => "Bearer {$supabaseKey}",
                ])->post("{$supabaseUrl}/rest/v1/taxi_location_updates", [
                    'trip_id'     => $activeTrip->id,
                    'driver_id'   => $request->user()->id,
                    'lat'         => $data['lat'],
                    'lng'         => $data['lng'],
                    'recorded_at' => now()->toIso8601String(),
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * POST /v1/taxi/driver/rides/{id}/accept
     * Driver accepts an incoming ride request.
     */
    public function accept(Request $request, string $id): JsonResponse
    {
        $driver = $request->user();

        // Guard: driver must not already have an active ride
        $hasActive = TaxiTrip::where('driver_id', $driver->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($hasActive) {
            return response()->json(['message' => 'You already have an active ride.'], 422);
        }

        $trip = TaxiTrip::findOrFail($id);

        if ($trip->status !== 'searching') {
            return response()->json(['message' => 'This ride is no longer available.'], 422);
        }

        DB::transaction(function () use ($trip, $driver) {
            // Lock the trip row to prevent race condition with multiple drivers
            $trip = TaxiTrip::where('id', $trip->id)->where('status', 'searching')->lockForUpdate()->first();

            if (!$trip) {
                throw new \RuntimeException('Ride already accepted by another driver.');
            }

            $trip->update([
                'driver_id'   => $driver->id,
                'status'      => 'accepted',
                'accepted_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'trip'    => $trip->fresh(['taxiCategory', 'customer.profile']),
        ]);
    }

    /**
     * POST /v1/taxi/driver/rides/{id}/arrive
     * Driver marks arrival at pickup location.
     */
    public function arrive(Request $request, string $id): JsonResponse
    {
        $trip = $this->resolveDriverTrip($request, $id, 'accepted');

        $trip->update([
            'status'     => 'driver_arrived',
            'arrived_at' => now(),
        ]);

        return response()->json(['success' => true, 'trip' => $trip->fresh()]);
    }

    /**
     * POST /v1/taxi/driver/rides/{id}/start
     * Driver starts the ride (customer confirmed on board).
     */
    public function startRide(Request $request, string $id): JsonResponse
    {
        $trip = $this->resolveDriverTrip($request, $id, 'driver_arrived');

        $trip->update([
            'status'     => 'in_transit',
            'started_at' => now(),
        ]);

        return response()->json(['success' => true, 'trip' => $trip->fresh()]);
    }

    /**
     * POST /v1/taxi/driver/rides/{id}/complete
     * Driver marks ride as complete. Handles fare, cash commission, quests, points.
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'final_fare'  => ['required', 'numeric', 'min:0'],
            'distance_km' => ['required', 'numeric', 'min:0'],
        ]);

        $trip = $this->resolveDriverTrip($request, $id, 'in_transit');

        $finalFare   = $data['final_fare'];
        $distanceKm  = $data['distance_km'];
        $commissionAmount = 0;

        DB::transaction(function () use ($trip, $finalFare, $distanceKm, &$commissionAmount) {
            $updates = [
                'status'       => 'completed',
                'completed_at' => now(),
                'final_fare'   => round($finalFare, 2),
                'distance_km'  => round($distanceKm, 2),
            ];

            if ($trip->payment_method === 'cash') {
                $commissionAmount = round($finalFare * 0.15, 2);
                $updates['cash_paid']         = true;
                $updates['commission_amount']  = $commissionAmount;
                $updates['commission_invoiced'] = false;
            }

            $trip->update($updates);

            // Award Pearl Points to customer (1 pt per LKR 100)
            $points = (int) floor($finalFare / 100);
            if ($points > 0) {
                DB::table('loyalty_points')->insert([
                    'user_id'    => $trip->customer_id,
                    'points'     => $points,
                    'source'     => 'taxi_ride',
                    'ref_id'     => $trip->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $trip->refresh();

        // Track quest progress for driver
        try {
            $this->questService->trackProgress($trip->driver_id, $trip);
            $this->questService->checkCompletion($trip->driver_id);
        } catch (\Throwable $e) {
            // Non-critical — log and continue
        }

        // If card payment, initiate payout to driver wallet
        if ($trip->payment_method === 'card') {
            $driverEarning = round($finalFare * 0.85, 2); // 85% to driver
            ProviderWallet::where('provider_id', $trip->driver_id)->increment('balance', $driverEarning);
        }

        return response()->json([
            'success'          => true,
            'trip'             => $trip,
            'commission_amount' => $commissionAmount,
        ]);
    }

    /**
     * GET /v1/taxi/driver/quests
     * Driver's active quest progress.
     */
    public function quests(Request $request): JsonResponse
    {
        $driverId = $request->user()->id;

        $progress = \App\Models\TaxiDriverQuestProgress::with('quest')
            ->where('driver_id', $driverId)
            ->get()
            ->map(fn($p) => [
                'quest'         => $p->quest,
                'current_value' => $p->current_value,
                'is_completed'  => $p->is_completed,
                'completed_at'  => $p->completed_at,
                'percent'       => $p->quest
                    ? min(100, ($p->current_value / $p->quest->target_value) * 100)
                    : 0,
            ]);

        return response()->json(['success' => true, 'data' => $progress]);
    }

    /**
     * GET /v1/taxi/driver/commission-invoices
     * Driver's cash commission invoice history.
     */
    public function commissionInvoices(Request $request): JsonResponse
    {
        $invoices = TaxiCashCommissionInvoice::where('driver_id', $request->user()->id)
            ->latest('period_start')
            ->paginate(10);

        return response()->json(['success' => true, 'data' => $invoices]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resolve a trip ensuring the requesting user is the driver and status matches.
     */
    private function resolveDriverTrip(Request $request, string $id, string $expectedStatus): TaxiTrip
    {
        $trip = TaxiTrip::findOrFail($id);

        abort_unless($trip->driver_id === $request->user()->id, 403, 'Forbidden.');
        abort_unless($trip->status === $expectedStatus, 422, "Expected trip status '{$expectedStatus}'.");

        return $trip;
    }
}
