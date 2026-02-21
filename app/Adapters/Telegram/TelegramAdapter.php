<?php

declare(strict_types=1);

namespace App\Adapters\Telegram;

use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Channel\Models\Channel;
use RuntimeException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use Throwable;

readonly class TelegramAdapter implements TelegramContract
{
    private function makeBot(string $botToken): Nutgram
    {
        return new Nutgram($botToken);
    }

    public function registerWebhook(Channel $channel, string $botToken, string $webhookSecret): string
    {
        $webhookUrl = rtrim(config('app.url'), '/') . "/api/webhook/telegram/{$channel->id}";

        try {
            $bot = $this->makeBot($botToken);
            $bot->setWebhook(
                url: $webhookUrl,
                allowed_updates: ['message', 'callback_query'],
                secret_token: $webhookSecret,
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Telegram setWebhook failed for channel [{$channel->id}]: {$e->getMessage()}"
            );
        }

        return $webhookUrl;
    }

    public function unregisterWebhook(Channel $channel, string $botToken): void
    {
        try {
            $bot = $this->makeBot($botToken);
            $bot->deleteWebhook();
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Telegram deleteWebhook failed for channel [{$channel->id}]: {$e->getMessage()}"
            );
        }
    }

    public function sendMessage(string $botToken, string $chatId, string $text, ?string $parseMode = 'HTML'): void
    {
        $bot = $this->makeBot($botToken);
        $bot->sendMessage(
            text: $text,
            chat_id: $chatId,
            parse_mode: $parseMode ? ParseMode::from($parseMode) : null,
        );
    }

    public function getFileUrl(string $botToken, string $fileId): string
    {
        $bot = $this->makeBot($botToken);
        $file = $bot->getFile($fileId);

        if (! $file?->file_path) {
            throw new RuntimeException("Could not get file path for file_id [{$fileId}]");
        }

        return "https://api.telegram.org/file/bot{$botToken}/{$file->file_path}";
    }
}
