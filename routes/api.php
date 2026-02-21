<?php

declare(strict_types=1);

use App\Http\Auth\AuthController;
use App\Http\Channels\ChannelController;
use App\Http\Middleware\VerifyTelegramWebhookSecret;
use App\Http\Webhook\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/google', [AuthController::class, 'google']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // Channels
    Route::apiResource('channels', ChannelController::class);
    Route::post('/channels/{channel}/activate', [ChannelController::class, 'activate']);
    Route::post('/channels/{channel}/deactivate', [ChannelController::class, 'deactivate']);
});

// Telegram webhook (protected by per-channel secret token)
Route::post('/webhook/telegram/{channel}', TelegramWebhookController::class)
    ->middleware(VerifyTelegramWebhookSecret::class);
