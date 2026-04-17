<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendOtpSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private readonly string $phone,
        private readonly string $code,
        private readonly string $purpose = 'auth'
    ) {}

    public function handle(): void
    {
        $message = match ($this->purpose) {
            'auth'           => "Your Grabber OTP is {$this->code}. Valid for 10 minutes. Do not share this code.",
            'password_reset' => "Your Grabber password reset code is {$this->code}. Valid for 10 minutes.",
            'phone_verify'   => "Verify your Grabber phone: {$this->code}. Valid for 10 minutes.",
            default          => "Your Grabber code is {$this->code}. Valid for 10 minutes.",
        };

        $apiUrl = config('services.sms.url');
        $apiKey = config('services.sms.key');

        if (!$apiUrl || !$apiKey) {
            Log::warning('SMS service not configured. OTP code for dev.', [
                'phone' => $this->phone,
                'code'  => $this->code,
            ]);
            return;
        }

        $response = Http::withHeaders(['Authorization' => "Bearer {$apiKey}"])
            ->post($apiUrl, [
                'to'      => $this->phone,
                'message' => $message,
            ]);

        if (!$response->successful()) {
            Log::error('SMS delivery failed.', [
                'phone'  => $this->phone,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            $this->fail(new \RuntimeException('SMS delivery failed: ' . $response->status()));
        }
    }
}
