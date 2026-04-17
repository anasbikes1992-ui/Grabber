<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiDriverScore extends Model
{
    use HasUuids;
    protected $table = 'taxi_driver_scores';

    protected $fillable = [
        'driver_id', 'period_start', 'period_end',
        'completed_rides', 'accepted_rides', 'received_requests',
        'avg_rating', 'avg_response_seconds', 'total_online_minutes',
        'acceptance_rate', 'completion_rate',
        'rating_score', 'acceptance_score', 'completion_score',
        'response_score', 'hours_score', 'total_score',
        'tier', 'total_km', 'bonus_earned', 'bonus_credited',
    ];

    protected $casts = [
        'period_start'         => 'date',
        'period_end'           => 'date',
        'avg_rating'           => 'float',
        'avg_response_seconds' => 'float',
        'acceptance_rate'      => 'float',
        'completion_rate'      => 'float',
        'rating_score'         => 'float',
        'acceptance_score'     => 'float',
        'completion_score'     => 'float',
        'response_score'       => 'float',
        'hours_score'          => 'float',
        'total_score'          => 'float',
        'total_km'             => 'float',
        'bonus_earned'         => 'float',
        'bonus_credited'       => 'boolean',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
