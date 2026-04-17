<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiCashCommissionInvoice extends Model
{
    protected $table = 'taxi_cash_commission_invoices';

    protected $fillable = [
        'driver_id', 'period_start', 'period_end',
        'total_cash_rides', 'total_cash_fares',
        'commission_rate', 'commission_amount',
        'status', 'payment_ref', 'paid_at', 'due_at',
        'suspension_triggered',
    ];

    protected $casts = [
        'period_start'          => 'date',
        'period_end'            => 'date',
        'total_cash_fares'      => 'float',
        'commission_rate'       => 'float',
        'commission_amount'     => 'float',
        'paid_at'               => 'datetime',
        'due_at'                => 'datetime',
        'suspension_triggered'  => 'boolean',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'unpaid'
            && $this->due_at !== null
            && $this->due_at->isPast();
    }
}
