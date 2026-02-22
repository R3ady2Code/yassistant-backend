<?php

declare(strict_types=1);

use App\Http\Auth\Controllers\AuthController;
use App\Http\BotSettings\Controllers\BotSettingsController;
use App\Http\Channels\Controllers\ChannelController;
use App\Http\Channels\Middleware\AuthorizeChannelOwner;
use App\Http\Conversations\Controllers\ConversationController;
use App\Http\Conversations\Middleware\AuthorizeConversationOwner;
use App\Http\Faq\Controllers\FaqController;
use App\Http\Faq\Middleware\AuthorizeFaqOwner;
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

    // Conversations
    Route::get('/conversations', [ConversationController::class, 'index']);

    Route::middleware(AuthorizeConversationOwner::class)->group(function () {
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
        Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
        Route::post('/conversations/{conversation}/takeover', [ConversationController::class, 'takeover']);
        Route::post('/conversations/{conversation}/release', [ConversationController::class, 'release']);
        Route::post('/conversations/{conversation}/toggle-ai', [ConversationController::class, 'toggleAi']);
        Route::post('/conversations/{conversation}/send', [ConversationController::class, 'send']);
        Route::post('/conversations/{conversation}/close', [ConversationController::class, 'close']);
    });

    // Bot Settings
    Route::get('/bot-settings', [BotSettingsController::class, 'show']);
    Route::post('/bot-settings/update', [BotSettingsController::class, 'update']);

    // FAQ
    Route::get('/faq', [FaqController::class, 'index']);
    Route::post('/faq', [FaqController::class, 'store']);
    Route::post('/faq/reorder', [FaqController::class, 'reorder']);

    Route::middleware(AuthorizeFaqOwner::class)->group(function () {
        Route::post('/faq/{faqEntry}/update', [FaqController::class, 'update']);
        Route::post('/faq/{faqEntry}/delete', [FaqController::class, 'destroy']);
    });
});

// Telegram webhook (protected by a per-channel secret token)
Route::post('/webhook/telegram/{channel}', TelegramWebhookController::class)
    ->middleware(VerifyTelegramWebhookSecret::class);
