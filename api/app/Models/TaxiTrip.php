<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiTrip extends Model
{
    use HasUuids;
    protected $table = 'taxi_trips';

    protected $fillable = [
        'driver_id',
        'customer_id',
        'taxi_category_id',
        'status',
        'origin_lat',
        'origin_lng',
        'origin_address',
        'dest_lat',
        'dest_lng',
        'dest_address',
        'stops',
        'distance_km',
        'duration_min',
        'estimated_fare',
        'final_fare',
        'surge_multiplier',
        'payment_method',
        'cash_paid',
        'commission_amount',
        'commission_invoiced',
        'accepted_at',
        'arrived_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'stops'               => 'array',
        'cash_paid'           => 'boolean',
        'commission_invoiced' => 'boolean',
        'origin_lat'          => 'float',
        'origin_lng'          => 'float',
        'dest_lat'            => 'float',
        'dest_lng'            => 'float',
        'estimated_fare'      => 'float',
        'final_fare'          => 'float',
        'surge_multiplier'    => 'float',
        'commission_amount'   => 'float',
        'accepted_at'         => 'datetime',
        'arrived_at'          => 'datetime',
        'started_at'          => 'datetime',
        'completed_at'        => 'datetime',
        'cancelled_at'        => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function taxiCategory(): BelongsTo
    {
        return $this->belongsTo(TaxiCategory::class);
    }
}
