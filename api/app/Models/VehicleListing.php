<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleListing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id', 'make', 'model', 'year', 'color', 'license_plate',
        'vehicle_type', 'transmission', 'seats', 'price_per_day', 'currency',
        'pickup_city', 'pickup_lat', 'pickup_lng', 'features', 'images',
        'status', 'rating_avg', 'review_count', 'driver_available',
        'driver_extra_per_day',
    ];

    protected $casts = [
        'features'          => 'array',
        'images'            => 'array',
        'pickup_lat'        => 'decimal:7',
        'pickup_lng'        => 'decimal:7',
        'price_per_day'     => 'decimal:2',
        'driver_extra_per_day' => 'decimal:2',
        'rating_avg'        => 'decimal:2',
        'driver_available'  => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
