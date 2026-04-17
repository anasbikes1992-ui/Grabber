<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * POST /v1/payments/initiate
     * Initiate a WebxPay card payment for a booking.
     */
    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'uuid'],
        ]);

        $booking = Booking::findOrFail($data['booking_id']);

        if ($booking->customer_id !== $request->user()->id) {
            abort(403, 'Forbidden.');
        }
        if ($booking->payment_status !== 'unpaid') {
            abort(422, 'Booking is already paid or cancelled.');
        }

        $handlingFeeEnabled = (bool) config('services.webxpay.handling_fee_enabled', false);
        $handlingFeeRate    = (float) config('services.webxpay.handling_fee_rate', 3.0);
        $handlingFee        = $handlingFeeEnabled
            ? round($booking->total_amount * ($handlingFeeRate / 100), 2)
            : 0.0;

        $chargeAmount = $booking->total_amount + $handlingFee;

        $payment = Payment::create([
            'booking_id'     => $booking->id,
            'payment_method' => 'card',
            'gateway'        => 'webxpay',
            'status'         => 'pending',
            'amount'         => $booking->total_amount,
            'handling_fee'   => $handlingFee,
            'vat_amount'     => $booking->vat_amount,
        ]);

        // Build WebxPay redirect payload
        $merchantId  = config('services.webxpay.merchant_id');
        $secretKey   = config('services.webxpay.secret_key');
        $callbackUrl = route('api.payments.webhook');
        $returnUrl   = config('services.webxpay.return_url');

        $bookingRef  = 'GRAB-' . strtoupper(substr($booking->id, 0, 8));
        $description = "Grabber Booking {$bookingRef}";

        $payload = [
            'merchant_id'  => $merchantId,
            'order_id'     => $payment->id,
            'amount'       => number_format($chargeAmount, 2, '.', ''),
            'currency'     => 'LKR',
            'description'  => $description,
            'return_url'   => $returnUrl,
            'callback_url' => $callbackUrl,
        ];
        ksort($payload);
        $payload['signature'] = hash_hmac('sha256', implode('|', $payload), $secretKey);

        return response()->json([
            'success'      => true,
            'payment_id'   => $payment->id,
            'charge_amount' => $chargeAmount,
            'handling_fee' => $handlingFee,
            'gateway_url'  => config('services.webxpay.gateway_url'),
            'payload'      => $payload,
        ]);
    }

    /**
     * POST /v1/payments/webhook (WebxPay callback — no auth guard)
     * Processes confirmed/failed gateway notifications.
     */
    public function webhook(Request $request): JsonResponse
    {
        // Signature already verified by VerifyWebxPaySignatureMiddleware
        $status    = $request->input('status');
        $paymentId = $request->input('order_id');
        $gatewayRef = $request->input('transaction_id');

        $payment = Payment::with('booking')->find($paymentId);
        if (!$payment) {
            Log::warning('WebxPay webhook: unknown payment_id', ['id' => $paymentId]);
            return response()->json(['received' => true]);
        }

        DB::transaction(function () use ($payment, $status, $gatewayRef, $request) {
            if ($status === 'success' || $status === 'APPROVED') {
                $payment->update([
                    'status'           => 'paid',
                    'gateway_ref'      => $gatewayRef,
                    'gateway_response' => $request->all(),
                ]);
                $payment->booking->update([
                    'status'         => 'confirmed',
                    'payment_status' => 'paid',
                ]);
                Log::info('WebxPay payment confirmed', ['payment_id' => $payment->id]);
            } else {
                $payment->update([
                    'status'           => 'failed',
                    'gateway_response' => $request->all(),
                ]);
                Log::warning('WebxPay payment failed', ['payment_id' => $payment->id]);
            }
        });

        return response()->json(['received' => true]);
    }

    /**
     * POST /v1/payments/confirm-bank-transfer
     * Admin/accounting confirms a bank transfer receipt.
     */
    public function confirmBankTransfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id'     => ['required', 'uuid'],
            'bank_tx_ref'    => ['required', 'string', 'max:100'],
        ]);

        $booking = Booking::with('payment')->findOrFail($data['booking_id']);

        abort_unless($booking->payment?->payment_method === 'bank_transfer', 422, 'Not a bank transfer booking.');

        DB::transaction(function () use ($booking, $data, $request) {
            $booking->payment->update([
                'status'            => 'paid',
                'bank_transfer_ref' => $data['bank_tx_ref'],
                'bank_confirmed_by' => $request->user()->id,
                'bank_confirmed_at' => now(),
            ]);
            $booking->update(['status' => 'confirmed', 'payment_status' => 'paid']);
        });

        return response()->json(['success' => true, 'message' => 'Bank transfer confirmed.']);
    }
}
