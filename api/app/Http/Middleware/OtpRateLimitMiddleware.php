<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class OtpRateLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $identifier = (string) ($request->input('phone') ?? $request->input('email') ?? $request->ip());
        $key = sprintf('otp:%s:%s', $request->ip(), $identifier);

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'Too many OTP requests. Please try again later.'
            ], 429);
        }

        RateLimiter::hit($key, 15 * 60);

        return $next($request);
    }
}
