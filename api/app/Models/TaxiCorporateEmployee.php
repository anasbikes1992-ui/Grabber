<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxiCorporateEmployee extends Model
{
    protected $table = 'taxi_corporate_employees';

    protected $fillable = [
        'corporate_account_id', 'user_id', 'monthly_limit', 'is_active',
    ];

    protected $casts = [
        'monthly_limit' => 'float',
        'is_active'     => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TaxiCorporateAccount::class, 'corporate_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
