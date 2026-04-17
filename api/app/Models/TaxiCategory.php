<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TaxiCategory extends Model
{
    use HasUuids;
    protected $fillable = [
        'name', 'slug', 'icon', 'description', 'base_fare', 'per_km_rate',
        'per_min_rate', 'minimum_fare', 'surge_enabled', 'max_surge_multiplier',
        'capacity', 'is_active',
    ];

    protected $casts = [
        'base_fare'            => 'decimal:2',
        'per_km_rate'          => 'decimal:2',
        'per_min_rate'         => 'decimal:2',
        'minimum_fare'         => 'decimal:2',
        'max_surge_multiplier' => 'decimal:2',
        'surge_enabled'        => 'boolean',
        'is_active'            => 'boolean',
    ];

    public function calculateFare(float $distanceKm, int $durationMin, float $surgeMultiplier = 1.0): float
    {
        $fare = $this->base_fare
            + ($distanceKm * $this->per_km_rate)
            + ($durationMin * $this->per_min_rate);

        $fare *= max(1.0, min($surgeMultiplier, (float) $this->max_surge_multiplier));
        return max($fare, (float) $this->minimum_fare);
    }
}
