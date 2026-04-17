<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'customer_id', 'provider_id', 'booking_type', 'bookable_type',
        'bookable_id', 'starts_at', 'ends_at', 'status', 'subtotal',
        'commission_amount', 'vat_amount', 'total_amount', 'currency',
        'payment_status', 'customer_notes', 'cancellation_reason',
        'cancelled_at', 'cancelled_by',
    ];

    protected $casts = [
        'starts_at'         => 'datetime',
        'ends_at'           => 'datetime',
        'cancelled_at'      => 'datetime',
        'subtotal'          => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'vat_amount'        => 'decimal:2',
        'total_amount'      => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function bookable()
    {
        return $this->morphTo();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForCustomer($query, string $userId)
    {
        return $query->where('customer_id', $userId);
    }

    public function scopeForProvider($query, string $userId)
    {
        return $query->where('provider_id', $userId);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->customer_id === $user->id || $this->provider_id === $user->id;
    }
}
