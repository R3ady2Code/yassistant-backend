<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Services;

use App\Domain\Booking\Models\BookingFlow;
use App\Domain\Booking\Models\BookingFlowStep;
use App\Domain\Conversation\Enums\PipelinePhase;
use App\Domain\Conversation\Models\Conversation;

final class BookingPipelineManager
{
    public function startFlow(Conversation $conversation, BookingFlow $flow): void
    {
        $firstStep = $flow->steps()->first();

        $conversation->update([
            'booking_flow_id' => $flow->id,
            'pipeline_state' => [
                'phase' => $firstStep
                    ? PipelinePhase::CustomQuestions->value
                    : ($flow->ask_staff
                        ? PipelinePhase::SelectStaff->value
                        : PipelinePhase::SelectDatetime->value),
                'current_step_id' => $firstStep?->id,
                'answers' => [],
            ],
        ]);
    }

    public function currentPhase(Conversation $conversation): ?PipelinePhase
    {
        return $conversation->pipeline_state
            ? PipelinePhase::from($conversation->pipeline_state['phase'])
            : null;
    }

    public function currentStep(Conversation $conversation): ?BookingFlowStep
    {
        $stepId = $conversation->pipeline_state['current_step_id'] ?? null;

        return $stepId ? BookingFlowStep::find($stepId) : null;
    }

    public function saveAnswerAndAdvance(
        Conversation $conversation,
        BookingFlowStep $step,
        mixed $value,
        string $rawInput,
    ): PipelinePhase {
        $state = $conversation->pipeline_state;

        $state['answers'][(string) $step->id] = [
            'question' => $step->question_text,
            'value' => $value,
            'raw' => $rawInput,
        ];

        $nextStep = BookingFlowStep::query()
            ->where('flow_id', $conversation->booking_flow_id)
            ->where('sort_order', '>', $step->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($nextStep) {
            $state['phase'] = PipelinePhase::CustomQuestions->value;
            $state['current_step_id'] = $nextStep->id;
        } else {
            $state['current_step_id'] = null;
            $flow = BookingFlow::find($conversation->booking_flow_id);
            $state['phase'] = $flow->ask_staff
                ? PipelinePhase::SelectStaff->value
                : PipelinePhase::SelectDatetime->value;
        }

        $conversation->update(['pipeline_state' => $state]);

        return PipelinePhase::from($state['phase']);
    }

    public function savePhaseDataAndAdvance(
        Conversation $conversation,
        array $data,
    ): PipelinePhase {
        $state = $conversation->pipeline_state;
        $state = array_merge($state, $data);

        $current = PipelinePhase::from($state['phase']);
        $next = match ($current) {
            PipelinePhase::SelectStaff => PipelinePhase::SelectDatetime,
            PipelinePhase::SelectDatetime => PipelinePhase::CollectContacts,
            PipelinePhase::CollectContacts => PipelinePhase::Confirm,
            PipelinePhase::Confirm => PipelinePhase::Complete,
            default => $current,
        };

        $state['phase'] = $next->value;
        $conversation->update(['pipeline_state' => $state]);

        return $next;
    }

    public function buildSummary(Conversation $conversation): string
    {
        $state = $conversation->pipeline_state;
        $flow = BookingFlow::find($conversation->booking_flow_id);

        $lines = ["Услуга: {$flow->yclients_service_name}"];

        if ($state['staff_name'] ?? null) {
            $lines[] = "Мастер: {$state['staff_name']}";
        }

        foreach ($state['answers'] as $a) {
            $displayValue = is_bool($a['value'])
                ? ($a['value'] ? 'Да' : 'Нет')
                : $a['value'];
            $lines[] = "{$a['question']}: {$displayValue}";
        }

        $lines[] = "Дата: {$state['date']}";
        $lines[] = "Время: {$state['time']}";
        $lines[] = "Имя: {$state['client_name']}";
        $lines[] = "Телефон: {$state['client_phone']}";

        return implode("\n", $lines);
    }

    public function buildYClientsComment(Conversation $conversation): string
    {
        $state = $conversation->pipeline_state;
        $parts = [];

        foreach ($state['answers'] as $a) {
            $displayValue = is_bool($a['value'])
                ? ($a['value'] ? 'Да' : 'Нет')
                : $a['value'];
            $parts[] = "{$a['question']}: {$displayValue}";
        }

        return implode('; ', $parts);
    }

    public function reset(Conversation $conversation): void
    {
        $conversation->update([
            'booking_flow_id' => null,
            'pipeline_state' => null,
        ]);
    }

    public function isActive(Conversation $conversation): bool
    {
        return $conversation->booking_flow_id !== null
            && $conversation->pipeline_state !== null;
    }
}
