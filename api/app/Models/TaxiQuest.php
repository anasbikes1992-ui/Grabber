<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxiQuest extends Model
{
    use HasUuids;
    protected $table = 'taxi_quests';

    protected $fillable = [
        'title', 'description', 'type', 'metric',
        'target_value', 'reward_amount', 'reward_type',
        'starts_at', 'ends_at', 'is_active', 'max_completions',
    ];

    protected $casts = [
        'target_value'    => 'float',
        'reward_amount'   => 'float',
        'starts_at'       => 'datetime',
        'ends_at'         => 'datetime',
        'is_active'       => 'boolean',
        'max_completions' => 'integer',
    ];

    public function progress(): HasMany
    {
        return $this->hasMany(TaxiDriverQuestProgress::class, 'quest_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }
}
