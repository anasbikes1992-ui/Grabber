<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaysListing extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'host_id', 'title', 'description', 'property_type', 'address',
        'lat', 'lng', 'city', 'base_price_per_night', 'currency',
        'max_guests', 'bedrooms', 'bathrooms', 'amenities', 'images',
        'status', 'rating_avg', 'review_count', 'instant_book',
        'min_nights', 'max_nights',
    ];

    protected $casts = [
        'amenities'       => 'array',
        'images'          => 'array',
        'lat'             => 'decimal:7',
        'lng'             => 'decimal:7',
        'base_price_per_night' => 'decimal:2',
        'rating_avg'      => 'decimal:2',
        'instant_book'    => 'boolean',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'bookable_id')
            ->where('booking_type', 'stay');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }
}
