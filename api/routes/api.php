<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'grabber-api',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    Route::get('/me', function (Request $request) {
        return response()->json(['user' => $request->user()]);
    })->middleware('auth:sanctum');
});
