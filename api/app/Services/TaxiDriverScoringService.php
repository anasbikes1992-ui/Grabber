<?php

namespace App\Services;

use App\Models\ProviderWallet;
use App\Models\TaxiDriverScore;
use App\Models\TaxiTrip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaxiDriverScoringService
{
    /**
     * Tier thresholds (score out of 100)
     */
    private const TIER_THRESHOLDS = [
        'diamond' => 85,
        'gold'    => 70,
        'silver'  => 55,
        'bronze'  => 0,
    ];

    /**
     * Bonus rate per km by tier (LKR)
     */
    private const TIER_BONUS_PER_KM = [
        'diamond' => 3.00,
        'gold'    => 2.00,
        'silver'  => 1.00,
        'bronze'  => 0.50,
    ];

    /**
     * Score all active drivers for last week (Mon–Sun).
     * Called by scheduler every Monday at 02:00.
     */
    public function scoreAllDrivers(): void
    {
        $periodEnd   = now()->startOfWeek()->subDay()->endOfDay();
        $periodStart = $periodEnd->copy()->startOfWeek();

        $drivers = User::where('role', 'driver')->where('is_active', true)->get();

        foreach ($drivers as $driver) {
            try {
                $this->scoreDriver($driver->id, $periodStart, $periodEnd);
            } catch (\Throwable $e) {
                Log::error("Driver scoring failed for {$driver->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Score a single driver for a specific period and credit bonus.
     */
    public function scoreDriver(string $driverId, $periodStart, $periodEnd): TaxiDriverScore
    {
        $trips = TaxiTrip::where('driver_id', $driverId)
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->get();

        $requested = TaxiTrip::where('driver_id', $driverId)
            ->orWhere(function ($q) use ($driverId, $periodStart, $periodEnd) {
                // Count all rides offered to this driver in the period
                $q->whereBetween('created_at', [$periodStart, $periodEnd]);
            })
            ->count();

        $completed        = $trips->where('status', 'completed')->count();
        $accepted         = $trips->whereNotNull('accepted_at')->count();
        $totalKm          = $trips->sum('distance_km') ?? 0;
        $avgRating        = $trips->whereNotNull('driver_rating')->avg('driver_rating') ?? 0;
        $avgResponseSecs  = $trips->whereNotNull('accepted_at')->avg(function ($t) {
            return $t->accepted_at?->diffInSeconds($t->created_at) ?? 0;
        }) ?? 0;

        // Online minutes — not stored yet, default to 480 (8 hours) per worked day
        $workedDays        = $trips->pluck('started_at')->filter()->map->format('Y-m-d')->unique()->count();
        $totalOnlineMinutes = $workedDays * 480;

        $acceptanceRate = $requested > 0 ? ($accepted / $requested) * 100 : 0;
        $completionRate = $accepted > 0 ? ($completed / $accepted) * 100 : 0;

        // Score sub-components (weighted, sum = 100)
        $ratingScore     = min(($avgRating / 5) * 40, 40);
        $acceptanceScore = min(($acceptanceRate / 100) * 20, 20);
        $completionScore = min(($completionRate / 100) * 20, 20);
        $responseScore   = min(max(0, (120 - $avgResponseSecs) / 120) * 10, 10);
        $hoursScore      = min(($totalOnlineMinutes / 2400) * 10, 10); // 40h/week = full score

        $totalScore = $ratingScore + $acceptanceScore + $completionScore + $responseScore + $hoursScore;
        $tier        = $this->determineTier($totalScore);
        $bonusEarned = $totalKm * self::TIER_BONUS_PER_KM[$tier];

        $existingScore = TaxiDriverScore::where('driver_id', $driverId)
            ->where('period_start', $periodStart->toDateString())
            ->first();

        $previousTier = $existingScore?->tier;

        $score = TaxiDriverScore::updateOrCreate(
            ['driver_id' => $driverId, 'period_start' => $periodStart->toDateString()],
            [
                'period_end'            => $periodEnd->toDateString(),
                'completed_rides'       => $completed,
                'accepted_rides'        => $accepted,
                'received_requests'     => $requested,
                'avg_rating'            => round($avgRating, 2),
                'avg_response_seconds'  => round($avgResponseSecs, 2),
                'total_online_minutes'  => $totalOnlineMinutes,
                'acceptance_rate'       => round($acceptanceRate, 2),
                'completion_rate'       => round($completionRate, 2),
                'rating_score'          => round($ratingScore, 2),
                'acceptance_score'      => round($acceptanceScore, 2),
                'completion_score'      => round($completionScore, 2),
                'response_score'        => round($responseScore, 2),
                'hours_score'           => round($hoursScore, 2),
                'total_score'           => round($totalScore, 2),
                'tier'                  => $tier,
                'total_km'              => round($totalKm, 2),
                'bonus_earned'          => round($bonusEarned, 2),
            ]
        );

        // Credit bonus to provider wallet
        if ($bonusEarned > 0 && !$score->bonus_credited) {
            $this->creditBonus($driverId, $bonusEarned, $score->id);
            $score->update(['bonus_credited' => true]);
        }

        // Notify driver if tier changed
        if ($previousTier && $previousTier !== $tier) {
            $this->notifyTierChange($driverId, $previousTier, $tier);
        }

        return $score;
    }

    private function determineTier(float $score): string
    {
        foreach (self::TIER_THRESHOLDS as $tier => $threshold) {
            if ($score >= $threshold) {
                return $tier;
            }
        }
        return 'bronze';
    }

    private function creditBonus(string $driverId, float $amount, string $scoreId): void
    {
        DB::transaction(function () use ($driverId, $amount) {
            $wallet = ProviderWallet::where('provider_id', $driverId)->lockForUpdate()->first();

            if (!$wallet) {
                $wallet = ProviderWallet::create([
                    'provider_id' => $driverId,
                    'balance'     => 0,
                ]);
            }

            $wallet->increment('balance', $amount);
        });
    }

    private function notifyTierChange(string $driverId, string $from, string $to): void
    {
        Log::info("Driver {$driverId} tier changed from {$from} to {$to}");
        // TODO: dispatch push notification via FCM through Supabase Edge Function
    }
}
