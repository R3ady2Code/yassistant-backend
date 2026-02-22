<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Models\BotSettings;
use App\Domain\Conversation\Models\Client;

final class PromptBuilder
{
    private const string DEFAULT_PROMPT = <<<'PROMPT'
Ты — AI-ассистент для записи клиентов. Помогай пользователям записаться на услуги, узнать расписание и получить информацию. Отвечай вежливо и кратко на русском языке.
PROMPT;

    public function build(BotSettings $settings, Client $client): string
    {
        $basePrompt = $settings->system_prompt ?? self::DEFAULT_PROMPT;

        $context = [];
        $context[] = $basePrompt;

        if ($client->name !== null) {
            $context[] = "Имя клиента: {$client->name}";
        }

        $context[] = 'Текущая дата и время: '.now()->format('d.m.Y H:i');

        return implode("\n\n", $context);
    }
}
