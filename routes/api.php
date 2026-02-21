<?php

declare(strict_types=1);

use App\Http\Auth\Controllers\AuthController;
use App\Http\Channels\Controllers\ChannelController;
use App\Http\Channels\Middleware\AuthorizeChannelOwner;
use App\Http\Webhook\Controllers\TelegramWebhookController;
use App\Http\Webhook\Middleware\VerifyTelegramWebhookSecret;
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
    Route::get('/channels', [ChannelController::class, 'index']);
    Route::post('/channels', [ChannelController::class, 'store']);

    Route::middleware(AuthorizeChannelOwner::class)->group(function () {
        Route::get('/channels/{channel}', [ChannelController::class, 'show']);
        Route::post('/channels/{channel}/update', [ChannelController::class, 'update']);
        Route::post('/channels/{channel}/delete', [ChannelController::class, 'destroy']);
        Route::post('/channels/{channel}/activate', [ChannelController::class, 'activate']);
        Route::post('/channels/{channel}/deactivate', [ChannelController::class, 'deactivate']);
    });
});

// Telegram webhook (protected by a per-channel secret token)
Route::post('/webhook/telegram/{channel}', TelegramWebhookController::class)
    ->middleware(VerifyTelegramWebhookSecret::class);
