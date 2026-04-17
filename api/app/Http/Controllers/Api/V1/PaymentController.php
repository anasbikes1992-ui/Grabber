<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\AwardPearlPointsJob;
use App\Jobs\ProviderWalletCreditJob;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\WebxPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private readonly WebxPayService $webxPay) {}

    /**
     * POST /v1/payments/initiate
     *
     * Returns the WebxPay gateway URL + all form fields needed for the client
     * to build a hidden HTML form and auto-submit to the hosted checkout page.
     *
     * WebxPay flow:
     *   plaintext = "{payment_id}|{charge_amount_LKR_int}"
     *   payment   = base64(RSA_encrypt(plaintext, public_key))
     *   POST form → https://webxpay.com/index.php?route=checkout/billing
     */
    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id' => ['required', 'uuid'],
        ]);

        $booking = Booking::with('customer.profile')->findOrFail($data['booking_id']);

        if ($booking->customer_id !== $request->user()->id) {
            abort(403, 'Forbidden.');
        }
        if ($booking->payment_status !== 'unpaid') {
            abort(422, 'Booking is already paid or in progress.');
        }

        // Handling fee (card surcharge)
        $handlingFeeEnabled = (bool) config('services.webxpay.handling_fee_enabled', false);
        $handlingFeeRate    = (float) config('services.webxpay.handling_fee_rate', 3.0);
        $handlingFee = $handlingFeeEnabled
            ? round($booking->total_amount * ($handlingFeeRate / 100), 2)
            : 0.0;

        // Create pending payment record
        $payment = Payment::create([
            'booking_id'        => $booking->id,
            'payer_id'          => $request->user()->id,
            'payment_method'    => 'card',
            'gateway'           => 'webxpay',
            'type'              => 'booking',
            'status'            => 'pending',
            'currency'          => $booking->currency,
            'amount'            => $booking->total_amount,
            'handling_fee'      => $handlingFee,
            'handling_fee_rate' => $handlingFeeEnabled ? $handlingFeeRate / 100 : 0,
        ]);

        // Build RSA-encrypted checkout payload
        $checkout = $this->webxPay->buildCheckoutPayload(
            $payment,
            $booking,
            $booking->customer
        );

        return response()->json([
            'success'       => true,
            'payment_id'    => $payment->id,
            'charge_amount' => $booking->total_amount + $handlingFee,
            'handling_fee'  => $handlingFee,
            'gateway_url'   => $checkout['gateway_url'],
            'form_data'     => $checkout['form_data'],
        ]);
    }

    /**
     * POST /v1/payments/callback  (WebxPay posts here after payment)
     *
     * WebxPay does NOT use HMAC headers — it echoes back the same secret_key
     * we submitted, plus status_code, order_id, transaction_id, custom_fields.
     *
     * Middleware: webxpay.signature (verifies secret_key field in POST body)
     */
    public function callback(Request $request): JsonResponse
    {
        $statusCode   = (string) $request->input('status_code', '');
        $customFields = (string) $request->input('custom_fields', '');
        $gatewayRef   = (string) $request->input('transaction_id', '');

        $ids = $this->webxPay->parseCustomFields($customFields);
        $payment = Payment::with('booking')->find($ids['payment_id']);

        if (!$payment) {
            Log::warning('WebxPay callback: payment not found', ['custom_fields' => $customFields]);
            return response()->json(['received' => true]);
        }

        // Status codes: '2' = success/approved, others = failure
        $isSuccess = $statusCode === '2';
        $dispatchFollowUps = false;

        DB::transaction(function () use ($payment, $isSuccess, $gatewayRef, $request, &$dispatchFollowUps) {
            $lockedPayment = Payment::with('booking')->lockForUpdate()->findOrFail($payment->id);

            if ($isSuccess) {
                $dispatchFollowUps = $lockedPayment->status !== 'completed';

                $lockedPayment->update([
                    'status'          => 'completed',
                    'gateway_ref'     => $gatewayRef,
                    'gateway_payload' => $request->all(),
                    'processed_at'    => now(),
                ]);
                $lockedPayment->booking->update([
                    'status'         => 'confirmed',
                    'payment_status' => 'paid',
                ]);
                Log::info('WebxPay: payment confirmed', [
                    'payment_id' => $lockedPayment->id,
                    'gateway_ref' => $gatewayRef,
                ]);
            } else {
                $lockedPayment->update([
                    'status'          => 'failed',
                    'gateway_payload' => $request->all(),
                    'processed_at'    => now(),
                ]);
                Log::warning('WebxPay: payment failed/declined', [
                    'payment_id'  => $lockedPayment->id,
                    'status_code' => $request->input('status_code'),
                ]);
            }
        });

        if ($isSuccess && $dispatchFollowUps) {
            ProviderWalletCreditJob::dispatch($payment->booking_id);
            AwardPearlPointsJob::dispatch($payment->booking_id);
        }

        return response()->json(['received' => true]);
    }

    /**
     * POST /v1/payments/confirm-bank-transfer
     * Finance team confirms a bank deposit received.
     */
    public function confirmBankTransfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id'  => ['required', 'uuid'],
            'bank_tx_ref' => ['required', 'string', 'max:100'],
        ]);

        $booking = Booking::with('payment')->findOrFail($data['booking_id']);
        abort_unless($booking->payment?->payment_method === 'bank_transfer', 422, 'Not a bank transfer booking.');

        $dispatchFollowUps = false;

        DB::transaction(function () use ($booking, $data, $request, &$dispatchFollowUps) {
            $lockedPayment = Payment::lockForUpdate()->findOrFail($booking->payment->id);
            $dispatchFollowUps = $lockedPayment->status !== 'completed';

            $lockedPayment->update([
                'status'            => 'completed',
                'bank_transfer_ref' => $data['bank_tx_ref'],
                'confirmed_by'      => $request->user()->id,
                'confirmed_at'      => now(),
                'processed_at'      => now(),
            ]);
            $booking->update(['status' => 'confirmed', 'payment_status' => 'paid']);
        });

        if ($dispatchFollowUps) {
            ProviderWalletCreditJob::dispatch($booking->id);
            AwardPearlPointsJob::dispatch($booking->id);
        }

        return response()->json(['success' => true, 'message' => 'Bank transfer confirmed.']);
    }

    /**
     * POST /v1/payments/confirm-cash-receipt
     * Cash agent confirms physical cash collected from customer.
     */
    public function confirmCashReceipt(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_id'          => ['required', 'uuid'],
            'cash_receipt_number' => ['required', 'string', 'max:60'],
        ]);

        $booking = Booking::with('payment')->findOrFail($data['booking_id']);
        abort_unless(
            in_array($booking->payment?->payment_method, ['cash_provider', 'cash_agent']),
            422,
            'Not a cash booking.'
        );

        $dispatchFollowUps = false;

        DB::transaction(function () use ($booking, $data, $request, &$dispatchFollowUps) {
            $lockedPayment = Payment::lockForUpdate()->findOrFail($booking->payment->id);
            $dispatchFollowUps = $lockedPayment->status !== 'completed';

            $lockedPayment->update([
                'status'              => 'completed',
                'cash_agent_id'       => $request->user()->id,
                'cash_receipt_number' => $data['cash_receipt_number'],
                'confirmed_by'        => $request->user()->id,
                'confirmed_at'        => now(),
                'processed_at'        => now(),
            ]);
            $booking->update(['status' => 'confirmed', 'payment_status' => 'paid']);
        });

        if ($dispatchFollowUps) {
            ProviderWalletCreditJob::dispatch($booking->id);
            AwardPearlPointsJob::dispatch($booking->id);
        }

        return response()->json(['success' => true, 'message' => 'Cash receipt confirmed.']);
    }
}
