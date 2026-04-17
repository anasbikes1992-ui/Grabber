<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_id', 'property_sale_id', 'subscription_id', 'flash_deal_id',
        'payment_method', 'gateway', 'status', 'amount', 'handling_fee',
        'vat_amount', 'gateway_ref', 'gateway_response',
        'bank_transfer_ref', 'bank_confirmed_by', 'bank_confirmed_at',
        'cash_agent_id', 'cash_receipt_number', 'cash_confirmed_at',
        'pearl_points_used', 'pearl_points_discount',
        'refund_amount', 'refunded_at', 'refund_reason',
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'handling_fee'        => 'decimal:2',
        'vat_amount'          => 'decimal:2',
        'pearl_points_discount' => 'decimal:2',
        'refund_amount'       => 'decimal:2',
        'bank_confirmed_at'   => 'datetime',
        'cash_confirmed_at'   => 'datetime',
        'refunded_at'         => 'datetime',
        'gateway_response'    => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bankConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bank_confirmed_by');
    }

    public function cashAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cash_agent_id');
    }
}
