<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PearlPointsBalance extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id', 'balance', 'lifetime_earned', 'lifetime_spent', 'tier',
    ];

    protected $casts = [
        'balance' => 'integer',
        'lifetime_earned' => 'integer',
        'lifetime_spent' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function earn(int $points): void
    {
        $this->increment('balance', $points);
        $this->increment('lifetime_earned', $points);
        $this->recalculateTier();
    }

    public function spend(int $points): bool
    {
        if ($this->balance < $points) {
            return false;
        }
        $this->decrement('balance', $points);
        $this->increment('lifetime_spent', $points);
        return true;
    }

    private function recalculateTier(): void
    {
        $tier = match(true) {
            $this->lifetime_earned >= 50000 => 'diamond',
            $this->lifetime_earned >= 20000 => 'gold',
            $this->lifetime_earned >= 5000  => 'silver',
            default => 'bronze',
        };
        if ($this->tier !== $tier) {
            $this->update(['tier' => $tier]);
        }
    }
}
