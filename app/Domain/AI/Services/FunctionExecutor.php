<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Enums\AIFunction;
use App\Domain\Booking\Actions\ValidateFlowStepAnswerAction;
use App\Domain\Booking\Models\BookingFlow;
use App\Domain\Booking\Models\BookingFlowStep;
use App\Domain\Conversation\Enums\PipelinePhase;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Services\BookingPipelineManager;

final class FunctionExecutor
{
    public const string ESCALATE_MARKER = '__ESCALATE__';

    public function __construct(
        private readonly BookingPipelineManager $pipelineManager,
        private readonly ValidateFlowStepAnswerAction $validateAnswer,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     */
    public function execute(AIFunction $function, array $args, Conversation $conversation): string
    {
        $result = match ($function) {
            AIFunction::EscalateToHuman => self::ESCALATE_MARKER,
            AIFunction::StartCustomFlow => $this->startCustomFlow($conversation, $args),
            AIFunction::SaveCustomAnswer => $this->saveCustomAnswer($conversation, $args),
            AIFunction::CancelPipeline => $this->cancelPipeline($conversation),
            default => ['error' => 'Функция временно недоступна'],
        };

        if ($result === self::ESCALATE_MARKER) {
            return self::ESCALATE_MARKER;
        }

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    private function startCustomFlow(Conversation $conversation, array $args): array
    {
        $serviceId = (int) ($args['service_id'] ?? 0);

        $flow = BookingFlow::query()
            ->where('tenant_id', $conversation->tenant_id)
            ->where('yclients_service_id', $serviceId)
            ->where('is_active', true)
            ->first();

        if (! $flow) {
            return ['error' => 'Кастомный flow не найден для этой услуги'];
        }

        $this->pipelineManager->startFlow($conversation, $flow);

        $firstStep = $flow->steps()->first();

        return [
            'status' => 'started',
            'service_name' => $flow->yclients_service_name,
            'first_question' => $firstStep ? [
                'step_id' => $firstStep->id,
                'question' => $firstStep->question_text,
                'expected' => $firstStep->describeExpectedAnswer(),
            ] : null,
            'phase' => $this->pipelineManager->currentPhase($conversation->refresh())->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function saveCustomAnswer(Conversation $conversation, array $args): array
    {
        $stepId = (int) ($args['step_id'] ?? 0);
        $extractedValue = (string) ($args['extracted_value'] ?? '');

        $step = BookingFlowStep::query()
            ->where('id', $stepId)
            ->where('flow_id', $conversation->booking_flow_id)
            ->first();

        if (! $step) {
            return ['error' => 'Шаг не найден'];
        }

        $validated = $this->validateAnswer->handle($step, $extractedValue);

        if ($validated === null) {
            return [
                'status' => 'invalid',
                'message' => "Ответ не подходит. Ожидается: {$step->describeExpectedAnswer()}",
            ];
        }

        $nextPhase = $this->pipelineManager->saveAnswerAndAdvance(
            $conversation, $step, $validated, $extractedValue,
        );

        $result = [
            'status' => 'saved',
            'phase' => $nextPhase->value,
        ];

        if ($nextPhase === PipelinePhase::CustomQuestions) {
            $nextStep = $this->pipelineManager->currentStep($conversation->refresh());

            if ($nextStep) {
                $result['next_question'] = [
                    'step_id' => $nextStep->id,
                    'question' => $nextStep->question_text,
                    'expected' => $nextStep->describeExpectedAnswer(),
                ];
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function cancelPipeline(Conversation $conversation): array
    {
        $this->pipelineManager->reset($conversation);

        return ['status' => 'cancelled'];
    }
}
