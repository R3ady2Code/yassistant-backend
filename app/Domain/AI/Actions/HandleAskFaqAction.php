<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Contracts\OpenAIContract;
use App\Domain\AI\DataObjects\OperationResult;
use App\Domain\AI\Enums\FallbackMessage;
use App\Domain\AI\Exceptions\InvalidClassificationException;
use App\Domain\AI\Models\BotSettings;
use App\Domain\AI\Models\FaqEntry;
use App\Domain\AI\Services\ResponseParser;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Models\Client;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Services\ConversationContextLoader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class HandleAskFaqAction extends AbstractAction
{
    private const int MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly OpenAIContract $openAI,
        private readonly ConversationContextLoader $contextLoader,
        private readonly ResponseParser $responseParser,
    ) {
        parent::__construct();
    }

    public function handle(BotSettings $settings, Client $client, Conversation $conversation): OperationResult
    {
        $faqEntries = FaqEntry::query()
            ->where('tenant_id', $settings->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $systemPrompt = $this->buildSystemPrompt($settings, $client, $faqEntries);
        $contextMessages = $this->contextLoader->load($conversation);
        $tool = $this->responseParser->buildTool();

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$contextMessages,
        ];

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $response = $this->openAI->chatCompletion($settings->ai_model, $messages, [$tool]);

            try {
                return $this->responseParser->parse($response);
            } catch (InvalidClassificationException) {
                continue;
            }
        }

        return new OperationResult(
            mode: ConversationMode::Escalated,
            responseText: FallbackMessage::Escalation->value,
        );
    }

    private function buildSystemPrompt(BotSettings $settings, Client $client, Collection $faqEntries): string
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

            foreach ($faqEntries as $entry) {
                $lines[] = "В: {$entry->question}";
                $lines[] = "О: {$entry->answer}";
                $lines[] = '';
            }
        }

        $lines[] = 'Ответь на вопрос клиента, используя информацию из FAQ. Если вопрос не относится к FAQ — вежливо сообщи, что не можешь помочь с этим.';
        $lines[] = 'Если клиент просит оператора или ты не можешь помочь — используй mode: escalated.';

        return implode("\n", $lines);
    }
}
