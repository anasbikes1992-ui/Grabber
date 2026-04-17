<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'booking_id', 'property_sale_id', 'subscription_id', 'flash_deal_id',
        'payer_id', 'payment_method', 'gateway', 'gateway_ref', 'gateway_payload',
        'amount', 'handling_fee', 'handling_fee_rate', 'currency', 'type', 'status',
        'refunded_amount', 'bank_transfer_ref', 'bank_transfer_deadline',
        'cash_agent_id', 'cash_receipt_number', 'cash_deadline',
        'confirmed_by', 'confirmed_at', 'processed_at',
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'handling_fee'        => 'decimal:2',
        'handling_fee_rate'   => 'decimal:4',
        'refunded_amount'     => 'decimal:2',
        'bank_transfer_deadline' => 'datetime',
        'cash_deadline'       => 'datetime',
        'confirmed_at'        => 'datetime',
        'processed_at'        => 'datetime',
        'gateway_payload'     => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bankConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cashAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cash_agent_id');
    }
}
