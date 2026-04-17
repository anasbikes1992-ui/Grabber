<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebxPaySignatureMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.webxpay.secret_key', env('WEBXPAY_SECRET_KEY', ''));
        if ($secret === '') {
            return response()->json(['message' => 'WebxPay secret is not configured.'], 500);
        }

        $incoming = (string) $request->header('x-webxpay-signature');
        $payload = (string) $request->getContent();
        $computed = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($computed, $incoming)) {
            return response()->json(['message' => 'Invalid WebxPay signature.'], 401);
        }

        return $next($request);
    }
}
