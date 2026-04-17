<?php

namespace App\Jobs;

use App\Models\TaxiTrip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifyDriverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly TaxiTrip $trip,
    ) {}

    /**
     * Find the nearest 5 online drivers and dispatch push notification
     * via Supabase Edge Function.
     */
    public function handle(): void
    {
        $trip = $this->trip;

        // Only search if ride is still in 'searching' state
        if ($trip->status !== 'searching') {
            return;
        }

        // Find 5 closest online drivers without active rides
        $nearbyDrivers = $this->findNearestDrivers($trip, 5);

        if ($nearbyDrivers->isEmpty()) {
            Log::warning("No online drivers found for trip {$trip->id}");
            return;
        }

        $supabaseUrl = config('services.supabase.url');
        $supabaseKey = config('services.supabase.service_key');

        if (!$supabaseUrl || !$supabaseKey) {
            Log::error('Supabase credentials not configured for driver notifications.');
            return;
        }

        $ridePayload = [
            'trip_id'       => $trip->id,
            'origin_lat'    => $trip->origin_lat,
            'origin_lng'    => $trip->origin_lng,
            'origin_address' => $trip->origin_address,
            'dest_address'  => $trip->dest_address,
            'category'      => $trip->taxiCategory?->name,
            'estimated_fare' => $trip->estimated_fare,
            'distance_km'   => $trip->distance_km,
            'surge'         => $trip->surge_multiplier,
            'payment_method' => $trip->payment_method,
        ];

        foreach ($nearbyDrivers as $driver) {
            try {
                Http::withHeaders([
                    'Authorization' => "Bearer {$supabaseKey}",
                    'Content-Type'  => 'application/json',
                ])->timeout(5)->post("{$supabaseUrl}/functions/v1/notify-driver", [
                    'driver_id'  => $driver->id,
                    'fcm_token'  => $driver->fcm_token ?? null,
                    'ride'       => $ridePayload,
                ]);
            } catch (\Throwable $e) {
                Log::warning("Failed to notify driver {$driver->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Find nearest online drivers sorted by proximity to trip origin.
     */
    private function findNearestDrivers(TaxiTrip $trip, int $limit): \Illuminate\Support\Collection
    {
        // Haversine approximation via raw SQL for ordering by distance
        $lat = $trip->origin_lat;
        $lng = $trip->origin_lng;

        return \App\Models\User::where('role', 'driver')
            ->where('is_online', true)
            ->where('is_active', true)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->whereDoesntHave('activeTaxiTrip')
            ->selectRaw("*, (
                6371 * acos(
                    cos(radians(?)) *
                    cos(radians(current_lat)) *
                    cos(radians(current_lng) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(current_lat))
                )
            ) AS distance_km", [$lat, $lng, $lat])
            ->orderBy('distance_km')
            ->limit($limit)
            ->get();
    }
}
