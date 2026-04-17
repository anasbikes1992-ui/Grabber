<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\PearlPointsBalance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AwardPearlPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Points awarded per booking type on completion.
     */
    private const POINTS_MAP = [
        'stays'       => 500,
        'stay'        => 500,
        'vehicles'    => 300,
        'vehicle'     => 300,
        'taxi'        => 100,
        'events'      => 200,
        'event'       => 200,
        'experiences' => 400,
        'experience'  => 400,
    ];

    /**
     * Pearl tier thresholds (points → tier name).
     */
    private const TIERS = [
        15000 => 'platinum',
        5000  => 'gold',
        1000  => 'silver',
        0     => 'bronze',
    ];

    public function __construct(public readonly string $bookingId) {}

    public function handle(): void
    {
        $booking = Booking::find($this->bookingId);

        if (!$booking) {
            Log::warning('AwardPearlPointsJob: booking not found', ['id' => $this->bookingId]);
            return;
        }

        if (!in_array($booking->status, ['confirmed', 'completed'], true) || $booking->payment_status !== 'paid') {
            Log::info('AwardPearlPointsJob: skipped (booking not confirmed/completed and paid)', ['id' => $this->bookingId]);
            return;
        }

        $pointsToAward = self::POINTS_MAP[$booking->booking_type] ?? 0;
        if ($pointsToAward === 0) {
            return;
        }

        DB::transaction(function () use ($booking, $pointsToAward) {
            $balance = PearlPointsBalance::lockForUpdate()->firstOrCreate(
                ['user_id' => $booking->customer_id],
                ['balance' => 0, 'tier' => 'bronze', 'lifetime_earned' => 0]
            );

            $newBalance  = $balance->balance + $pointsToAward;
            $newLifetime = $balance->lifetime_earned + $pointsToAward;
            $newTier     = $this->resolveTier($newLifetime);

            $balance->update([
                'balance'         => $newBalance,
                'lifetime_earned' => $newLifetime,
                'tier'            => $newTier,
            ]);

            Log::info('AwardPearlPointsJob: awarded', [
                'booking_id' => $booking->id,
                'customer'   => $booking->customer_id,
                'points'     => $pointsToAward,
                'new_tier'   => $newTier,
            ]);
        });
    }

    private function resolveTier(int $lifetimePoints): string
    {
        foreach (self::TIERS as $threshold => $tier) {
            if ($lifetimePoints >= $threshold) {
                return $tier;
            }
        }
        return 'bronze';
    }
}
