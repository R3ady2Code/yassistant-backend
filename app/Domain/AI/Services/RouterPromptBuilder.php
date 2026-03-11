<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Models\BotSettings;
use App\Domain\AI\Models\FaqEntry;
use App\Domain\Conversation\Models\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class RouterPromptBuilder
{
    /**
     * @param  Collection<int, FaqEntry>  $faqEntries
     */
    public function build(BotSettings $settings, Client $client, Collection $faqEntries): string
    {
        $lines = [];
        $lines[] = $settings->system_prompt ?? 'Ты — AI-ассистент. Отвечай вежливо и кратко на русском языке.';

        if ($client->name !== null) {
            $lines[] = "Имя клиента: {$client->name}";
        }

        $lines[] = 'Текущая дата и время: '.Carbon::now()->format('d.m.Y H:i');

        if ($faqEntries->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## FAQ';
            $lines[] = 'Используй эту информацию для ответов на вопросы клиента.';
            $lines[] = '';

            foreach ($faqEntries as $entry) {
                $lines[] = "В: {$entry->question}";
                $lines[] = "О: {$entry->answer}";
                $lines[] = '';
            }
        }

        $lines[] = '## Инструкции';
        $lines[] = '- Если можешь ответить на вопрос клиента (в том числе из FAQ) — используй send_response.';
        $lines[] = '- Если клиент хочет записаться на услугу — используй create_booking.';
        $lines[] = '- Если клиент хочет отменить запись — используй cancel_booking.';
        $lines[] = '- Если клиент хочет изменить запись — используй edit_booking.';
        $lines[] = '- Если клиент просит оператора или ты не можешь помочь — используй escalate_to_human.';

        return implode("\n", $lines);
    }
}
