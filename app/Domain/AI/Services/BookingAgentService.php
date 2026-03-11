<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\DataObjects\OperationResult;
use App\Domain\AI\Enums\FallbackMessage;
use App\Domain\AI\Models\BotSettings;
use App\Domain\Booking\Models\BookingFlow;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Models\Client;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Services\BookingPipelineManager;
use App\Domain\Conversation\Services\ConversationContextLoader;
use Illuminate\Support\Carbon;

final class BookingAgentService
{
    public function __construct(
        private readonly AICompletionService $completionService,
        private readonly ConversationContextLoader $contextLoader,
        private readonly BookingPipelineManager $pipelineManager,
    ) {}

    public function handle(BotSettings $settings, Client $client, Conversation $conversation): OperationResult
    {
        $flow = BookingFlow::query()
            ->where('tenant_id', $conversation->tenant_id)
            ->where('is_active', true)
            ->with('steps')
            ->first();

        if ($flow === null) {
            return new OperationResult(
                mode: ConversationMode::AI,
                responseText: FallbackMessage::CreateBookingUnavailable->value,
            );
        }

        if (! $this->pipelineManager->isActive($conversation)) {
            $this->pipelineManager->startFlow($conversation, $flow);
            $conversation->refresh();
        }

        $systemPrompt = $this->buildSystemPrompt($settings, $client, $flow, $conversation);
        $contextMessages = $this->contextLoader->load($conversation);

        $operationResult = $this->completionService->complete($settings->ai_model, $systemPrompt, $contextMessages);

        if ($operationResult) {
            return $operationResult;
        }

        return new OperationResult(
            mode: ConversationMode::Escalated,
            responseText: FallbackMessage::Escalation->value,
        );
    }

    private function buildSystemPrompt(
        BotSettings $settings,
        Client $client,
        BookingFlow $flow,
        Conversation $conversation,
    ): string {
        $lines = [];
        $lines[] = $settings->system_prompt ?? 'Ты — AI-ассистент для записи клиентов. Отвечай вежливо и кратко на русском языке.';

        if ($client->name !== null) {
            $lines[] = "Имя клиента: {$client->name}";
        }

        $lines[] = 'Текущая дата и время: '.Carbon::now()->format('d.m.Y H:i');

        $lines[] = '';
        $lines[] = "## Flow записи: \"{$flow->name}\"";
        $lines[] = '';
        $lines[] = 'Шаги flow:';

        foreach ($flow->steps as $index => $step) {
            $num = $index + 1;
            $lines[] = "{$num}. \"{$step->question_text}\" (ожидается: {$step->describeExpectedAnswer()})";
        }

        $currentStep = $this->pipelineManager->currentStep($conversation);

        if ($currentStep !== null) {
            $lines[] = '';
            $lines[] = "Текущий вопрос: \"{$currentStep->question_text}\"";
            $lines[] = "Ожидаемый ответ: {$currentStep->describeExpectedAnswer()}";
        }

        $state = $conversation->pipeline_state;
        $answers = $state['answers'] ?? [];

        if (! empty($answers)) {
            $lines[] = '';
            $lines[] = 'Уже собранные ответы:';

            foreach ($answers as $answer) {
                $displayValue = is_bool($answer['value'])
                    ? ($answer['value'] ? 'Да' : 'Нет')
                    : $answer['value'];
                $lines[] = "- {$answer['question']}: {$displayValue}";
            }
        }

        $lines[] = '';
        $lines[] = 'Задай клиенту текущий вопрос. Если клиент хочет отменить — используй mode: ai.';
        $lines[] = 'Если клиент просит оператора — используй mode: escalated.';

        return implode("\n", $lines);
    }
}
