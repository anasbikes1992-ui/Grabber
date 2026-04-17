<?php

namespace App\Services;

use App\Models\ProviderWallet;
use App\Models\TaxiDriverQuestProgress;
use App\Models\TaxiQuest;
use App\Models\TaxiTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaxiQuestService
{
    /**
     * Track progress for a driver after a ride is completed.
     */
    public function trackProgress(string $driverId, TaxiTrip $trip): void
    {
        $activeQuests = TaxiQuest::active()->get();

        foreach ($activeQuests as $quest) {
            $delta = $this->computeDelta($quest, $trip);

            if ($delta <= 0) {
                continue;
            }

            $progress = TaxiDriverQuestProgress::firstOrCreate(
                ['driver_id' => $driverId, 'quest_id' => $quest->id],
                ['current_value' => 0, 'is_completed' => false]
            );

            if ($progress->is_completed) {
                continue;
            }

            $progress->increment('current_value', $delta);
            $progress->refresh();

            if ($progress->current_value >= $quest->target_value) {
                $this->completeQuest($progress, $quest);
            }
        }
    }

    /**
     * Check and complete all pending quests for a driver.
     * Call after every ride.
     */
    public function checkCompletion(string $driverId): void
    {
        $pending = TaxiDriverQuestProgress::where('driver_id', $driverId)
            ->where('is_completed', false)
            ->with('quest')
            ->get();

        foreach ($pending as $progress) {
            $quest = $progress->quest;

            if (!$quest || !$quest->is_active) {
                continue;
            }

            if ($progress->current_value >= $quest->target_value) {
                $this->completeQuest($progress, $quest);
            }
        }
    }

    /**
     * Reset daily quests (clear progress). Called every midnight.
     */
    public function resetDailyQuests(): void
    {
        $dailyQuestIds = TaxiQuest::where('type', 'daily')->pluck('id');

        TaxiDriverQuestProgress::whereIn('quest_id', $dailyQuestIds)->delete();
    }

    /**
     * Reset weekly quests. Called every Monday at 00:30.
     */
    public function resetWeeklyQuests(): void
    {
        $weeklyQuestIds = TaxiQuest::where('type', 'weekly')->pluck('id');

        TaxiDriverQuestProgress::whereIn('quest_id', $weeklyQuestIds)->delete();
    }

    private function completeQuest(TaxiDriverQuestProgress $progress, TaxiQuest $quest): void
    {
        $progress->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        if (!$progress->reward_credited) {
            $this->creditReward($progress->driver_id, $quest);
            $progress->update(['reward_credited' => true]);
        }
    }

    private function creditReward(string $driverId, TaxiQuest $quest): void
    {
        if ($quest->reward_type !== 'cash') {
            return;
        }

        DB::transaction(function () use ($driverId, $quest) {
            $wallet = ProviderWallet::where('provider_id', $driverId)->lockForUpdate()->first();

            if (!$wallet) {
                $wallet = ProviderWallet::create([
                    'provider_id' => $driverId,
                    'balance'     => 0,
                ]);
            }

            $wallet->increment('balance', $quest->reward_amount);
        });

        Log::info("Quest reward LKR {$quest->reward_amount} credited to driver {$driverId} for quest '{$quest->title}'");
    }

    /**
     * Compute how much a completed trip contributes to a quest metric.
     */
    private function computeDelta(TaxiQuest $quest, TaxiTrip $trip): float
    {
        return match ($quest->metric) {
            'completed_rides' => $trip->status === 'completed' ? 1 : 0,
            'km_driven'       => (float) ($trip->distance_km ?? 0),
            'online_minutes'  => 0, // tracked separately via driver status
            'rating'          => (float) ($trip->driver_rating ?? 0),
            default           => 0,
        };
    }
}
