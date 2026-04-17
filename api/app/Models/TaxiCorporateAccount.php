<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxiCorporateAccount extends Model
{
    use HasUuids;
    protected $table = 'taxi_corporate_accounts';

    protected $fillable = [
        'company_name', 'company_reg_no', 'contact_name', 'contact_email',
        'contact_phone', 'billing_address', 'billing_cycle',
        'credit_limit', 'current_usage', 'discount_percent', 'is_active',
    ];

    protected $casts = [
        'credit_limit'     => 'float',
        'current_usage'    => 'float',
        'discount_percent' => 'float',
        'is_active'        => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(TaxiCorporateEmployee::class, 'corporate_account_id');
    }

    public function remainingCredit(): float
    {
        return max(0, $this->credit_limit - $this->current_usage);
    }
}
