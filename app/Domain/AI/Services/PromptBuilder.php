<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Models\BotSettings;
use App\Domain\AI\Models\FaqEntry;
use App\Domain\Conversation\Models\Client;
use Illuminate\Support\Carbon;

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

        $context[] = 'Текущая дата и время: '.Carbon::now()->format('d.m.Y H:i');

        $faqBlock = $this->buildFaqBlock($settings->tenant_id);

        if ($faqBlock !== null) {
            $context[] = $faqBlock;
        }

        return implode("\n\n", $context);
    }

    private function buildFaqBlock(string $tenantId): ?string
    {
        $entries = FaqEntry::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($entries->isEmpty()) {
            return null;
        }

        $lines = ['## Часто задаваемые вопросы (FAQ)'];

        foreach ($entries as $entry) {
            $lines[] = "В: {$entry->question}";
            $lines[] = "О: {$entry->answer}";
        }

        return implode("\n\n", $lines);
    }
}
