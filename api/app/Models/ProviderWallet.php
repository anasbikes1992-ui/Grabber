<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderWallet extends Model
{
    use HasUuids;
    protected $fillable = [
        'provider_id', 'balance', 'on_hold', 'lifetime_earnings',
        'lifetime_payouts', 'cash_commission_outstanding', 'is_frozen',
        'payout_hold_days', 'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'on_hold' => 'decimal:2',
        'lifetime_earnings' => 'decimal:2',
        'lifetime_payouts' => 'decimal:2',
        'cash_commission_outstanding' => 'decimal:2',
        'is_frozen' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function credit(float $amount, string $description = ''): void
    {
        $this->increment('balance', $amount);
        $this->increment('lifetime_earnings', $amount);
    }

    public function debit(float $amount): bool
    {
        if ($this->balance < $amount) {
            return false;
        }
        $this->decrement('balance', $amount);
        return true;
    }

    public function hold(float $amount): void
    {
        $this->decrement('balance', $amount);
        $this->increment('on_hold', $amount);
    }

    public function releaseHold(float $amount): void
    {
        $this->decrement('on_hold', $amount);
        $this->increment('balance', $amount);
    }
}
