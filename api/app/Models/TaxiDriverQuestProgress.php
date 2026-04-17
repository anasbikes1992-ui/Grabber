<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxiDriverQuestProgress extends Model
{
    protected $table = 'taxi_driver_quest_progress';

    protected $fillable = [
        'driver_id', 'quest_id', 'current_value',
        'is_completed', 'completed_at', 'reward_credited',
    ];

    protected $casts = [
        'current_value'  => 'float',
        'is_completed'   => 'boolean',
        'completed_at'   => 'datetime',
        'reward_credited' => 'boolean',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(TaxiQuest::class, 'quest_id');
    }
}
