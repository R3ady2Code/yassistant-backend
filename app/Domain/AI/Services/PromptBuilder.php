<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Models\BotSettings;
use App\Domain\AI\Models\FaqEntry;
use App\Domain\Booking\Models\BookingFlow;
use App\Domain\Conversation\Enums\PipelinePhase;
use App\Domain\Conversation\Models\Client;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Services\BookingPipelineManager;
use Illuminate\Support\Carbon;

final class PromptBuilder
{
    private const string DEFAULT_PROMPT = <<<'PROMPT'
Ты — AI-ассистент для записи клиентов. Помогай пользователям записаться на услуги, узнать расписание и получить информацию. Отвечай вежливо и кратко на русском языке.
PROMPT;

    public function __construct(
        private readonly BookingPipelineManager $pipelineManager,
    ) {}

    public function build(BotSettings $settings, Client $client, Conversation $conversation): string
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

        $customFlowsBlock = $this->buildCustomFlowsBlock($conversation->tenant_id);

        if ($customFlowsBlock !== null) {
            $context[] = $customFlowsBlock;
        }

        if ($this->pipelineManager->isActive($conversation)) {
            $context[] = $this->buildPipelineBlock($conversation);
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

    private function buildCustomFlowsBlock(string $tenantId): ?string
    {
        $flows = BookingFlow::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        if ($flows->isEmpty()) {
            return null;
        }

        $lines = ['Для следующих услуг настроена автоматическая запись:'];

        foreach ($flows as $flow) {
            $lines[] = "- \"{$flow->yclients_service_name}\" (service_id: {$flow->yclients_service_id}) — вызови start_custom_flow";
        }

        $lines[] = '';
        $lines[] = 'Для остальных услуг используй стандартный flow (get_services, get_available_slots, create_booking).';

        return implode("\n", $lines);
    }

    private function buildPipelineBlock(Conversation $conversation): string
    {
        $state = $conversation->pipeline_state;
        $flow = BookingFlow::find($conversation->booking_flow_id);
        $phase = PipelinePhase::from($state['phase']);

        $lines = ['--- РЕЖИМ ЗАПИСИ ---'];
        $lines[] = "Клиент записывается на услугу: \"{$flow->yclients_service_name}\"";
        $lines[] = 'Услуга уже выбрана, НЕ спрашивай какую услугу хочет клиент.';

        if ($phase === PipelinePhase::CustomQuestions) {
            $step = $this->pipelineManager->currentStep($conversation);

            if ($step) {
                $lines[] = '';
                $lines[] = 'Текущий этап: кастомный вопрос';
                $lines[] = "Вопрос: \"{$step->question_text}\"";
                $lines[] = "Ожидаемый ответ: {$step->describeExpectedAnswer()}";
                $lines[] = '';
                $lines[] = 'Из ответа клиента извлеки значение и вызови save_custom_answer.';
                $lines[] = 'Если ответ непонятен — переспроси дружелюбно.';
                $lines[] = 'Если клиент хочет отменить запись — вызови cancel_pipeline.';
            }
        } else {
            $answers = $state['answers'] ?? [];

            if (! empty($answers)) {
                $lines[] = '';
                $lines[] = 'Собранные данные:';

                foreach ($answers as $a) {
                    $displayValue = is_bool($a['value'])
                        ? ($a['value'] ? 'Да' : 'Нет')
                        : $a['value'];
                    $lines[] = "- {$a['question']}: {$displayValue}";
                }
            }

            $phaseInstruction = match ($phase) {
                PipelinePhase::SelectStaff => "\nТекущий этап: выбор мастера\nИспользуй функцию get_staff чтобы показать доступных мастеров.",
                PipelinePhase::SelectDatetime => "\nТекущий этап: выбор даты и времени\nИспользуй функцию get_available_slots чтобы показать свободные слоты.",
                PipelinePhase::CollectContacts => "\nТекущий этап: сбор контактных данных\nСпроси имя и телефон клиента для записи.",
                PipelinePhase::Confirm => "\nТекущий этап: подтверждение записи\n".$this->pipelineManager->buildSummary($conversation)."\nСпроси клиента, всё ли верно.",
                PipelinePhase::Complete => "\nТекущий этап: создание записи\nВызови create_booking для завершения записи.",
                default => '',
            };

            $lines[] = $phaseInstruction;
            $lines[] = 'Если клиент хочет отменить запись — вызови cancel_pipeline.';
        }

        return implode("\n", $lines);
    }
}
