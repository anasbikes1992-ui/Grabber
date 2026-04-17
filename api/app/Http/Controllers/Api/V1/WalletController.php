<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProviderWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * GET /v1/wallet
     * Provider's wallet balance summary.
     */
    public function balance(Request $request): JsonResponse
    {
        $wallet = ProviderWallet::firstOrCreate(
            ['provider_id' => $request->user()->id],
            ['balance' => 0, 'on_hold' => 0, 'lifetime_earnings' => 0]
        );

        return response()->json([
            'success' => true,
            'wallet'  => [
                'available_balance' => $wallet->balance,
                'pending_balance'   => $wallet->on_hold,
                'total_earned'      => $wallet->lifetime_earnings,
                'currency'          => $wallet->currency,
            ],
        ]);
    }

    /**
     * GET /v1/wallet/history
     * Paginated transaction history for the provider.
     */
    public function history(Request $request): JsonResponse
    {
        // Wallet transactions are derived from completed bookings where the user
        // is the provider/driver/host. We join through bookings.
        $userId = $request->user()->id;

        $transactions = DB::table('bookings')
            ->leftJoin('payments', 'payments.booking_id', '=', 'bookings.id')
            ->where('bookings.provider_id', $userId)
            ->where('bookings.payment_status', 'paid')
            ->select([
                'bookings.id',
                'bookings.booking_type',
                'bookings.total_amount',
                'bookings.commission_amount',
                'bookings.ends_at',
                'bookings.updated_at',
                'payments.status as payment_status',
                'payments.gateway_ref',
            ])
            ->orderByDesc('bookings.updated_at')
            ->paginate(25);

        return response()->json(['success' => true, 'data' => $transactions]);
    }

    /**
     * POST /v1/wallet/payout
     * Provider requests a bank payout.
     *
     * Minimum: LKR 5,000 | Platform fee: LKR 50
     */
    public function requestPayout(Request $request): JsonResponse
    {
        $minPayout   = 5000;
        $payoutFee   = 50;

        $data = $request->validate([
            'amount'       => ['required', 'numeric', "min:{$minPayout}", 'max:500000'],
            'bank_name'    => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_no'   => ['required', 'string', 'max:30'],
            'branch'       => ['nullable', 'string', 'max:100'],
        ]);

        $requestedAmount = (float) $data['amount'];
        $netAmount       = $requestedAmount - $payoutFee;

        DB::transaction(function () use ($requestedAmount, $netAmount, $payoutFee, $data, $request) {
            $wallet = ProviderWallet::where('provider_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if (!$wallet || $wallet->balance < $requestedAmount) {
                abort(422, 'Insufficient wallet balance.');
            }

            $wallet->decrement('balance', $requestedAmount);
            $wallet->increment('on_hold', $requestedAmount);

            DB::table('wallet_payout_requests')->insert([
                'id'               => DB::raw('gen_random_uuid()'),
                'user_id'          => $request->user()->id,
                'requested_amount' => $requestedAmount,
                'payout_fee'       => $payoutFee,
                'net_amount'       => $netAmount,
                'bank_name'        => $data['bank_name'],
                'account_name'     => $data['account_name'],
                'account_no'       => $data['account_no'],
                'branch'           => $data['branch'] ?? null,
                'status'           => 'pending',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        });

        return response()->json([
            'success'          => true,
            'message'          => 'Payout request submitted.',
            'requested_amount' => $requestedAmount,
            'payout_fee'       => $payoutFee,
            'net_amount'       => $netAmount,
        ], 201);
    }
}
