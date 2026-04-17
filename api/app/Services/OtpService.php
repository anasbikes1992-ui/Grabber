<?php

namespace App\Services;

use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OtpService
{
    private const CODE_LENGTH     = 6;
    private const TTL_MINUTES     = 10;
    private const MAX_ATTEMPTS    = 3;
    private const RESEND_COOLDOWN = 60; // seconds

    /**
     * Generate and persist a new OTP for the given identifier.
     * Enforces a resend cooldown to prevent spam.
     */
    public function generateAndStore(
        string $identifier,
        string $identifierType,
        string $purpose = 'auth',
        string $ipAddress = ''
    ): Otp {
        $cooldownKey = "otp_cooldown:{$identifierType}:{$identifier}";

        if (Cache::has($cooldownKey)) {
            throw ValidationException::withMessages([
                'identifier' => ['Please wait before requesting another OTP.'],
            ]);
        }

        // Invalidate any existing unused OTPs for this identifier+purpose
        Otp::where('identifier', $identifier)
            ->where('identifier_type', $identifierType)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        $otp = Otp::create([
            'identifier'      => $identifier,
            'identifier_type' => $identifierType,
            'purpose'         => $purpose,
            'code'            => $code,
            'expires_at'      => Carbon::now()->addMinutes(self::TTL_MINUTES),
            'ip_address'      => $ipAddress,
        ]);

        Cache::put($cooldownKey, true, self::RESEND_COOLDOWN);

        return $otp;
    }

    /**
     * Verify a code against the latest valid OTP for the identifier.
     * Returns the Otp on success; throws ValidationException on failure.
     */
    public function verify(
        string $identifier,
        string $identifierType,
        string $code,
        string $purpose = 'auth'
    ): Otp {
        $otp = Otp::where('identifier', $identifier)
            ->where('identifier_type', $identifierType)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$otp) {
            throw ValidationException::withMessages([
                'code' => ['No active OTP found. Please request a new one.'],
            ]);
        }

        if ($otp->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['OTP has expired. Please request a new one.'],
            ]);
        }

        $otp->increment('attempts');

        if ($otp->attempts > self::MAX_ATTEMPTS) {
            $otp->update(['used_at' => now()]);
            throw ValidationException::withMessages([
                'code' => ['Too many failed attempts. Please request a new OTP.'],
            ]);
        }

        if (!hash_equals($otp->code, $code)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid OTP. Please try again.'],
            ]);
        }

        $otp->update(['used_at' => now()]);

        return $otp;
    }

    /**
     * Check whether an OTP was recently verified (within the auth window).
     * Used for two-step flows: verify OTP → then register.
     */
    public function wasRecentlyVerified(string $identifier, string $identifierType): bool
    {
        return Otp::where('identifier', $identifier)
            ->where('identifier_type', $identifierType)
            ->where('purpose', 'auth')
            ->whereNotNull('used_at')
            ->where('used_at', '>=', now()->subMinutes(15))
            ->exists();
    }
}
