<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        private readonly string $email,
        private readonly string $code,
        private readonly string $purpose = 'auth'
    ) {}

    public function handle(): void
    {
        $subject = match ($this->purpose) {
            'auth'           => 'Your Grabber Sign-In Code',
            'password_reset' => 'Reset Your Grabber Password',
            'phone_verify'   => 'Verify Your Grabber Account',
            default          => 'Your Grabber Verification Code',
        };

        Mail::send([], [], function (Message $message) use ($subject) {
            $message
                ->to($this->email)
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->subject($subject)
                ->html($this->buildHtml());
        });
    }

    private function buildHtml(): string
    {
        $code    = $this->code;
        $year    = date('Y');
        $company = 'Grabber Mobility Solutions Pvt Ltd';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center" style="padding:40px 20px">
              <table width="560" style="background:#fff;border-radius:12px;overflow:hidden">
                <tr><td style="background:#1B6CA8;padding:24px;text-align:center">
                  <h1 style="color:#fff;margin:0;font-size:24px">Grabber</h1>
                </td></tr>
                <tr><td style="padding:32px;text-align:center">
                  <p style="font-size:16px;color:#333">Your verification code is:</p>
                  <div style="font-size:42px;font-weight:700;letter-spacing:12px;color:#1B6CA8;padding:16px 0">{$code}</div>
                  <p style="font-size:14px;color:#666">Valid for 10 minutes. Do not share this code with anyone.</p>
                </td></tr>
                <tr><td style="background:#f9f9f9;padding:16px;text-align:center">
                  <p style="font-size:12px;color:#999;margin:0">&copy; {$year} {$company}</p>
                </td></tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;
    }
}
