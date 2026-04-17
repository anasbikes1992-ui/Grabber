<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use RuntimeException;

/**
 * WebxPay payment gateway integration.
 *
 * Flow:
 *  1. Backend encrypts `order_id|amount` with RSA public key.
 *  2. Returns form-post parameters to the client.
 *  3. Client renders a hidden HTML form and auto-submits to the gateway URL.
 *  4. WebxPay processes the payment and POSTs status back to our callback URL.
 *  5. Callback verified by matching the secret_key value.
 */
class WebxPayService
{
    private string $gatewayUrl;
    private string $publicKey;
    private string $secretKey;
    private string $gatewayId;
    private string $encMethod;

    public function __construct()
    {
        $this->gatewayUrl = (string) config('services.webxpay.gateway_url');
        $this->publicKey  = (string) config('services.webxpay.public_key');
        $this->secretKey  = (string) config('services.webxpay.secret_key');
        $this->gatewayId  = (string) config('services.webxpay.gateway_id', '');
        $this->encMethod  = (string) config('services.webxpay.enc_method', 'JCs3J+6oSz4V0LgE0zi/Bg==');
    }

    /**
     * Build the complete form-post payload for redirecting the customer to WebxPay.
     *
     * @param  Payment  $payment   The payment record (must have id, amount, handling_fee)
     * @param  Booking  $booking   The associated booking
     * @param  User     $customer  The customer being charged
     * @return array{gateway_url: string, form_data: array<string,string>}
     */
    public function buildCheckoutPayload(Payment $payment, Booking $booking, User $customer): array
    {
        $chargeAmount = (int) round($payment->amount + $payment->handling_fee);

        // WebxPay plaintext format: unique_order_id|total_amount
        $plaintext = $payment->id . '|' . $chargeAmount;

        if (!openssl_public_encrypt($plaintext, $encrypted, $this->publicKey)) {
            throw new RuntimeException('WebxPay: RSA encryption failed — check WEBXPAY_PUBLIC_KEY.');
        }

        $paymentParam = base64_encode($encrypted);

        // custom_fields: base64 of pipe-separated booking metadata (4 fields max)
        $customFields = base64_encode(implode('|', [
            $booking->id,           // cus_1 = booking UUID
            $payment->id,           // cus_2 = payment UUID
            $booking->booking_type, // cus_3 = booking type
            '',                     // cus_4 = reserved
        ]));

        $profile = $customer->profile;
        [$firstName, $lastName] = $this->splitName($profile?->full_name ?? 'Customer');

        $callbackUrl = route('api.payments.callback');

        return [
            'gateway_url' => $this->gatewayUrl,
            'form_data'   => [
                'first_name'                  => $firstName,
                'last_name'                   => $lastName,
                'email'                       => $customer->email ?? '',
                'contact_number'              => $customer->phone ?? '',
                'address_line_one'            => $profile?->address ?? 'N/A',
                'address_line_two'            => '',
                'city'                        => 'Colombo',
                'state'                       => 'Western',
                'postal_code'                 => '00100',
                'country'                     => 'Sri Lanka',
                'process_currency'            => 'LKR',
                'payment_gateway_id'          => $this->gatewayId,
                'multiple_payment_gateway_ids' => '',
                'cms'                         => 'Laravel',
                'custom_fields'               => $customFields,
                'enc_method'                  => $this->encMethod,
                // Hidden fields
                'secret_key'                  => $this->secretKey,
                'payment'                     => $paymentParam,
                // Notify URL (WebxPay calls this on completion)
                'notify_url'                  => $callbackUrl,
            ],
        ];
    }

    /**
     * Verify a WebxPay callback is genuine.
     * WebxPay POSTs back the same secret_key we submitted — compare it.
     */
    public function verifyCallback(string $incomingSecretKey): bool
    {
        return hash_equals($this->secretKey, $incomingSecretKey);
    }

    /**
     * Parse the custom_fields sent back by WebxPay to recover our IDs.
     *
     * @return array{booking_id: string, payment_id: string, booking_type: string}
     */
    public function parseCustomFields(string $customFields): array
    {
        $decoded = base64_decode($customFields, strict: true);
        if ($decoded === false) {
            return ['booking_id' => '', 'payment_id' => '', 'booking_type' => ''];
        }
        [$bookingId, $paymentId, $bookingType] = array_pad(explode('|', $decoded), 3, '');
        return [
            'booking_id'   => $bookingId,
            'payment_id'   => $paymentId,
            'booking_type' => $bookingType,
        ];
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function splitName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);
        return [$parts[0], $parts[1] ?? ''];
    }
}
