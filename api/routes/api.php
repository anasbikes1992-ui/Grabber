<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\StaysListingController;
use App\Http\Controllers\Api\V1\VehicleListingController;
use Illuminate\Support\Facades\Route;

// ─── Health (unauthenticated) ─────────────────────────────────────────────────
Route::get('/v1/health', function () {
    return response()->json([
        'status'  => 'ok',
        'service' => 'Grabber API',
        'version' => '1.0.0',
        'time'    => now()->toIso8601String(),
    ]);
});

// ─── Auth (unauthenticated) ───────────────────────────────────────────────────
Route::prefix('v1/auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp'])->middleware('otp.rate_limit');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/register', [AuthController::class, 'register']);
});

// ─── WebxPay Webhook (no auth — signature verified by middleware) ─────────────
Route::post('/v1/payments/webhook', [PaymentController::class, 'webhook'])
    ->middleware('webxpay.signature')
    ->name('api.payments.webhook');

// ─── Public listing endpoints ─────────────────────────────────────────────────
Route::prefix('v1')->group(function () {
    Route::get('/stays', [StaysListingController::class, 'index']);
    Route::get('/stays/{id}', [StaysListingController::class, 'show']);
    Route::get('/vehicles', [VehicleListingController::class, 'index']);
    Route::get('/vehicles/{id}', [VehicleListingController::class, 'show']);
    Route::get('/reviews', [ReviewController::class, 'index']);
});

// ─── Authenticated endpoints ──────────────────────────────────────────────────
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // ── Current user ──────────────────────────────────────────────────────────
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // ── Stays (provider write) ────────────────────────────────────────────────
    Route::get('/stays/mine', [StaysListingController::class, 'mine']);
    Route::post('/stays', [StaysListingController::class, 'store']);
    Route::patch('/stays/{id}', [StaysListingController::class, 'update']);
    Route::delete('/stays/{id}', [StaysListingController::class, 'destroy']);

    // ── Vehicles (provider write) ─────────────────────────────────────────────
    Route::get('/vehicles/mine', [VehicleListingController::class, 'mine']);
    Route::post('/vehicles', [VehicleListingController::class, 'store']);
    Route::patch('/vehicles/{id}', [VehicleListingController::class, 'update']);
    Route::delete('/vehicles/{id}', [VehicleListingController::class, 'destroy']);

    // ── Bookings ──────────────────────────────────────────────────────────────
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // ── Payments ──────────────────────────────────────────────────────────────
    Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
    Route::post('/payments/confirm-bank-transfer', [PaymentController::class, 'confirmBankTransfer']);

    // ── Reviews ───────────────────────────────────────────────────────────────
    Route::post('/reviews', [ReviewController::class, 'store']);

    // ── Notifications ─────────────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
});
