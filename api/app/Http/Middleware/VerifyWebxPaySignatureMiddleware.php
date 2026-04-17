<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyWebxPaySignatureMiddleware
{
    /**
     * WebxPay callback verification.
     *
     * WebxPay does NOT use HMAC or signed headers. Instead it echoes back
     * the same secret_key that was submitted in the originating form POST.
     * We verify that value matches our configured key using timing-safe comparison.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredSecret = (string) config('services.webxpay.secret_key', '');
        if ($configuredSecret === '') {
            return response()->json(['message' => 'WebxPay secret_key is not configured.'], 500);
        }

        $postedSecret = (string) $request->input('secret_key', '');

        if (!hash_equals($configuredSecret, $postedSecret)) {
            Log::warning('WebxPay callback: invalid secret_key', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Unauthorized WebxPay callback.'], 401);
        }

        return $next($request);
    }
}
