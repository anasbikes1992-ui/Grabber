<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\ProviderWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProviderWalletCreditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $bookingId)
    {
    }

    public function handle(): void
    {
        $booking = Booking::find($this->bookingId);

        if (!$booking || $booking->payment_status !== 'paid') {
            Log::warning('ProviderWalletCreditJob: skipped because booking is not paid', [
                'booking_id' => $this->bookingId,
            ]);
            return;
        }

        $providerId = $booking->provider_id;

        if (!$providerId) {
            Log::warning('ProviderWalletCreditJob: skipped because provider is missing', [
                'booking_id' => $this->bookingId,
            ]);
            return;
        }

        $creditAmount = (float) $booking->total_amount - (float) $booking->commission_amount - (float) $booking->vat_amount;

        if ($creditAmount <= 0) {
            return;
        }

        DB::transaction(function () use ($providerId, $creditAmount, $booking) {
            $wallet = ProviderWallet::lockForUpdate()->firstOrCreate(
                ['provider_id' => $providerId],
                ['balance' => 0, 'on_hold' => 0, 'lifetime_earnings' => 0, 'lifetime_payouts' => 0]
            );

            $wallet->increment('balance', $creditAmount);
            $wallet->increment('lifetime_earnings', $creditAmount);

            Log::info('ProviderWalletCreditJob: credited provider wallet', [
                'provider_id' => $providerId,
                'booking_id' => $booking->id,
                'credit_amount' => $creditAmount,
            ]);
        });
    }
}