<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CspHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'self';"
        );

        return $response;
    }
}
