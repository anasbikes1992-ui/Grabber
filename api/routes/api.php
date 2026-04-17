<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\StaysListingController;
use App\Http\Controllers\Api\V1\VehicleListingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TaxiController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\EventsListingController;
use App\Http\Controllers\Api\V1\ExperiencesListingController;

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
Route::post('/v1/payments/callback', [PaymentController::class, 'callback'])
    ->middleware('webxpay.signature')
    ->name('api.payments.callback');

// ─── Public listing endpoints ─────────────────────────────────────────────────
Route::prefix('v1')->group(function () {
    Route::get('/stays', [StaysListingController::class, 'index']);
    Route::get('/stays/{id}', [StaysListingController::class, 'show']);
    Route::get('/vehicles', [VehicleListingController::class, 'index']);
    Route::get('/vehicles/{id}', [VehicleListingController::class, 'show']);
    Route::get('/reviews', [ReviewController::class, 'index']);
    // Events & Experiences — public browse
    Route::get('/events', [EventsListingController::class, 'index']);
    Route::get('/events/{id}', [EventsListingController::class, 'show']);
    Route::get('/experiences', [ExperiencesListingController::class, 'index']);
    Route::get('/experiences/{id}', [ExperiencesListingController::class, 'show']);
    // Taxi categories & fare estimate (public)
    Route::get('/taxi/categories', [TaxiController::class, 'categories']);
    Route::get('/taxi/estimate', [TaxiController::class, 'estimate']);
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
    Route::post('/payments/confirm-cash-receipt', [PaymentController::class, 'confirmCashReceipt']);

    // ── Reviews ───────────────────────────────────────────────────────────────
    Route::post('/reviews', [ReviewController::class, 'store']);

    // ── Notifications ─────────────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // ── Taxi ──────────────────────────────────────────────────────────────────
    Route::post('/taxi/rides', [TaxiController::class, 'requestRide']);
    Route::get('/taxi/active', [TaxiController::class, 'activeRide']);
    Route::get('/taxi/rides', [TaxiController::class, 'history']);
    Route::patch('/taxi/rides/{id}/status', [TaxiController::class, 'updateStatus']);

    // ── Wallet (provider) ─────────────────────────────────────────────────────
    Route::get('/wallet', [WalletController::class, 'balance']);
    Route::get('/wallet/history', [WalletController::class, 'history']);
    Route::post('/wallet/payout', [WalletController::class, 'requestPayout']);

    // ── Events (organiser write) ──────────────────────────────────────────────
    Route::get('/events/mine', [EventsListingController::class, 'mine']);
    Route::post('/events', [EventsListingController::class, 'store']);
    Route::put('/events/{id}', [EventsListingController::class, 'update']);
    Route::delete('/events/{id}', [EventsListingController::class, 'destroy']);
    Route::patch('/events/{id}/publish', [EventsListingController::class, 'publish']);

    // ── Experiences (provider write) ──────────────────────────────────────────
    Route::get('/experiences/mine', [ExperiencesListingController::class, 'mine']);
    Route::post('/experiences', [ExperiencesListingController::class, 'store']);
    Route::put('/experiences/{id}', [ExperiencesListingController::class, 'update']);
    Route::delete('/experiences/{id}', [ExperiencesListingController::class, 'destroy']);
});
