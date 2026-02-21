<?php

declare(strict_types=1);

namespace App\Domain\Channel\Contracts;

use App\Domain\Channel\Models\Channel;

interface TelegramContract
{
    public function registerWebhook(Channel $channel, string $botToken, string $webhookSecret): string;

    public function unregisterWebhook(Channel $channel, string $botToken): void;

    public function sendMessage(string $botToken, string $chatId, string $text, ?string $parseMode = 'HTML', ?array $replyMarkup = null): void;

    public function answerCallbackQuery(string $botToken, string $callbackQueryId, ?string $text = null): void;

    public function getFileUrl(string $botToken, string $fileId): string;
}
