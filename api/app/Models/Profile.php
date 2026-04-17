<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id', 'full_name', 'avatar_url', 'bio', 'nic_number',
        'date_of_birth', 'gender', 'address', 'preferred_lang',
        'preferred_currency', 'bank_name', 'bank_account_number',
        'bank_account_name', 'bank_branch', 'mobile_money_number',
        'provider_tier', 'is_online', 'last_lat', 'last_lng',
        'account_status', 'social_tier', 'referral_code',
        'cash_security_deposit_paid', 'cash_commission_invoice_day',
        'payout_hold_days',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_online' => 'boolean',
        'cash_security_deposit_paid' => 'boolean',
        'last_lat' => 'decimal:7',
        'last_lng' => 'decimal:7',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
