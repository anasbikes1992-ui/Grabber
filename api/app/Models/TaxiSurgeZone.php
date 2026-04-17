<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxiSurgeZone extends Model
{
    use HasUuids;
    protected $table = 'taxi_surge_zones';

    protected $fillable = [
        'name', 'city', 'center_lat', 'center_lng', 'radius_km',
        'base_surge', 'max_surge', 'auto_surge_enabled',
        'manual_override_multiplier', 'manual_override_until',
        'peak_hour_bonuses', 'is_active',
    ];

    protected $casts = [
        'center_lat'                  => 'float',
        'center_lng'                  => 'float',
        'radius_km'                   => 'float',
        'base_surge'                  => 'float',
        'max_surge'                   => 'float',
        'auto_surge_enabled'          => 'boolean',
        'manual_override_multiplier'  => 'float',
        'manual_override_until'       => 'datetime',
        'peak_hour_bonuses'           => 'array',
        'is_active'                   => 'boolean',
    ];

    public function isManuallyOverridden(): bool
    {
        return $this->manual_override_multiplier !== null
            && $this->manual_override_until !== null
            && $this->manual_override_until->isFuture();
    }

    public function effectiveMultiplier(): float
    {
        if ($this->isManuallyOverridden()) {
            return (float) $this->manual_override_multiplier;
        }

        return (float) $this->base_surge;
    }
}
