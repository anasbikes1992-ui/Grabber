<?php

use App\Http\Middleware\CspHeadersMiddleware;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\OtpRateLimitMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\VerifyWebxPaySignatureMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->append(CspHeadersMiddleware::class);

        $middleware->alias([
            'otp.rate_limit' => OtpRateLimitMiddleware::class,
            'role' => EnsureUserHasRole::class,
            'webxpay.signature' => VerifyWebxPaySignatureMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
