<?php

namespace App\Services;

use App\Models\TaxiSurgeZone;
use App\Models\TaxiTrip;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TaxiSurgeService
{
    private const CACHE_TTL_SECONDS = 30;
    private const SUPPLY_DEMAND_WINDOW_MINUTES = 5;

    /**
     * Calculate the surge multiplier for a given location.
     *
     * @param  float  $lat
     * @param  float  $lng
     * @return float  Surge multiplier (e.g. 1.0, 1.5, 2.0)
     */
    public function calculateSurge(float $lat, float $lng): float
    {
        $zone = $this->findZone($lat, $lng);

        if (!$zone) {
            return 1.0;
        }

        $cacheKey = "taxi_surge_zone_{$zone->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($zone) {
            return $this->computeMultiplier($zone);
        });
    }

    /**
     * Compute the surge multiplier for a specific zone.
     */
    public function computeMultiplier(TaxiSurgeZone $zone): float
    {
        // Manual override takes priority
        if ($zone->isManuallyOverridden()) {
            return (float) $zone->manual_override_multiplier;
        }

        if (!$zone->auto_surge_enabled) {
            return (float) $zone->base_surge;
        }

        // Count online drivers in zone (simplified — PostGIS in production)
        $onlineDrivers = DB::table('users')
            ->where('role', 'driver')
            ->where('is_online', true)
            ->count();

        // Pending ride requests in last 5 minutes
        $pendingRequests = TaxiTrip::where('status', 'searching')
            ->where('created_at', '>=', now()->subMinutes(self::SUPPLY_DEMAND_WINDOW_MINUTES))
            ->count();

        $demandRatio = $onlineDrivers > 0
            ? $pendingRequests / $onlineDrivers
            : ($pendingRequests > 0 ? 3.0 : 1.0);

        // Base multiplier from demand/supply ratio
        $multiplier = match (true) {
            $demandRatio >= 2.0 => $zone->base_surge + 1.0,
            $demandRatio >= 1.5 => $zone->base_surge + 0.5,
            $demandRatio >= 1.0 => $zone->base_surge,
            default             => 1.0,
        };

        // Peak hour bonuses from zone config
        $multiplier += $this->peakHourBonus($zone);

        // Round to nearest 0.5
        $multiplier = round($multiplier * 2) / 2;

        // Cap at zone max_surge
        return (float) min($multiplier, $zone->max_surge);
    }

    /**
     * Set a manual override for a zone.
     */
    public function setManualOverride(TaxiSurgeZone $zone, float $multiplier, int $minutes): void
    {
        $zone->update([
            'manual_override_multiplier' => $multiplier,
            'manual_override_until'      => now()->addMinutes($minutes),
        ]);

        Cache::forget("taxi_surge_zone_{$zone->id}");
    }

    /**
     * Clear the manual override for a zone.
     */
    public function clearManualOverride(TaxiSurgeZone $zone): void
    {
        $zone->update([
            'manual_override_multiplier' => null,
            'manual_override_until'      => null,
        ]);

        Cache::forget("taxi_surge_zone_{$zone->id}");
    }

    /**
     * Find the nearest surge zone within its radius.
     */
    private function findZone(float $lat, float $lng): ?TaxiSurgeZone
    {
        // Simple Euclidean proximity — use PostGIS ST_DWithin in production
        return TaxiSurgeZone::where('is_active', true)->get()->first(function ($zone) use ($lat, $lng) {
            $distance = $this->haversineKm($lat, $lng, $zone->center_lat, $zone->center_lng);
            return $distance <= $zone->radius_km;
        });
    }

    /**
     * Calculate additional surge from peak hour configuration.
     */
    private function peakHourBonus(TaxiSurgeZone $zone): float
    {
        $bonuses = $zone->peak_hour_bonuses ?? [];
        $now     = now()->format('H:i');

        foreach ($bonuses as $range => $bonus) {
            [$start, $end] = explode('-', $range);
            if ($now >= $start && $now <= $end) {
                return (float) $bonus;
            }
        }

        return 0.0;
    }

    /**
     * Haversine distance in km between two coordinates.
     */
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
