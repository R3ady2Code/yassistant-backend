<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Enums\AIFunction;
use App\Domain\AI\Enums\BotOperation;
use App\Domain\AI\Models\BotSettings;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Services\BookingPipelineManager;

final readonly class FunctionRegistry
{
    /**
     * Which BotOperation enables which AI functions.
     */
    private const array OPERATION_FUNCTIONS = [
        'create_booking' => [
            AIFunction::GetBranches,
            AIFunction::GetServices,
            AIFunction::GetStaff,
            AIFunction::GetAvailableSlots,
            AIFunction::CreateBooking,
            AIFunction::StartCustomFlow,
        ],
        'cancel_booking' => [],
        'edit_booking' => [],
    ];

    /**
     * Functions available only when a booking pipeline is active.
     */
    private const array PIPELINE_FUNCTIONS = [
        AIFunction::SaveCustomAnswer,
        AIFunction::CancelPipeline,
    ];

    public function __construct(
        private BookingPipelineManager $pipelineManager,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forTenant(BotSettings $settings, Conversation $conversation): array
    {
        $enabledFunctions = $this->resolveEnabledFunctions($settings);
        $pipelineActive = $this->pipelineManager->isActive($conversation);

        $tools = [];

        foreach (AIFunction::cases() as $function) {
            if ($function === AIFunction::EscalateToHuman) {
                $tools[] = $this->buildDefinition($function);

                continue;
            }

            if (in_array($function, self::PIPELINE_FUNCTIONS, true)) {
                if ($pipelineActive) {
                    $tools[] = $this->buildDefinition($function);
                }

                continue;
            }

            if (in_array($function, $enabledFunctions, true)) {
                $tools[] = $this->buildDefinition($function);
            }
        }

        return $tools;
    }

    /**
     * @return AIFunction[]
     */
    private function resolveEnabledFunctions(BotSettings $settings): array
    {
        $functions = [];

        foreach (BotOperation::cases() as $operation) {
            if ($settings->isOperationEnabled($operation)) {
                $functions = array_merge($functions, self::OPERATION_FUNCTIONS[$operation->value] ?? []);
            }
        }

        return array_unique($functions);
    }

    /**
     * @return array{type: string, function: array<string, mixed>}
     */
    private function buildDefinition(AIFunction $function): array
    {
        return match ($function) {
            AIFunction::GetBranches => [
                'type' => 'function',
                'function' => [
                    'name' => $function->value,
                    'description' => 'Получить список филиалов компании',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass,
                    ],
                ],
            ],
            AIFunction::GetServices => [
                'type' => 'function',
                'function' => [
                    'name' => $function->value,
                    'description' => 'Получить список доступных услуг',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'branch_id' => [
                                'type' => 'string',
                                'description' => 'ID филиала',
                            ],
                        ],
                        'required' => ['branch_id'],
                    ],
                ],
            ],
            AIFunction::GetStaff => [
                'type' => 'function',
                'function' => [
                    'name' => $function->value,
                    'description' => 'Получить список мастеров/специалистов',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'branch_id' => [
                                'type' => 'string',
                                'description' => 'ID филиала',
                            ],
                            'service_id' => [
                                'type' => 'string',
                                'description' => 'ID услуги (опционально, для фильтрации)',
                            ],
                        ],
                        'required' => ['branch_id'],
                    ],
                ],
            ],
            AIFunction::GetAvailableSlots => [
                'type' => 'function',
                'function' => [
                    'name' => $function->value,
                    'description' => 'Получить доступные слоты для записи',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'branch_id' => [
                                'type' => 'string',
                                'description' => 'ID филиала',
                            ],
                            'staff_id' => [
                                'type' => 'string',
                                'description' => 'ID мастера',
                            ],
                            'service_id' => [
                                'type' => 'string',
                                'description' => 'ID услуги',
                            ],
                            'date' => [
                                'type' => 'string',
                                'description' => 'Дата в формате YYYY-MM-DD',
                            ],
                        ],
                        'required' => ['branch_id', 'staff_id', 'service_id', 'date'],
                    ],
                ],
            ],
            AIFunction::CreateBooking => [
                'type' => 'function',
                'function' => [
                    'name' => $function->value,
                    'description' => 'Создать запись клиента на услугу',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'branch_id' => [
                                'type' => 'string',
                                'description' => 'ID филиала',
                            ],
                            'staff_id' => [
                                'type' => 'string',
                                'description' => 'ID мастера',
                            ],
                            'service_id' => [
                                'type' => 'string',
                                'description' => 'ID услуги',
                            ],
                            'datetime' => [
                                'type' => 'string',
                                'description' => 'Дата и время записи в формате YYYY-MM-DD HH:MM',
                            ],
                            'client_name' => [
                                'type' => 'string',
                                'description' => 'Имя клиента',
                            ],
                            'client_phone' => [
                                'type' => 'string',
                                'description' => 'Телефон клиента',
                            ],
                        ],
                        'required' => ['branch_id', 'staff_id', 'service_id', 'datetime', 'client_name', 'client_phone'],
                    ],
                ],
            ],
            AIFunction::StartCustomFlow => [
                'type' => 'function',
                'function' => [
                    'name' => $function->value,
                    'description' => 'Начать запись по кастомному flow. Вызывай когда определил что клиент хочет услугу, для которой есть кастомный flow.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'service_id' => [
                                'type' => 'integer',
                                'description' => 'ID услуги в YClients',
                            ],
                        ],
                        'required' => ['service_id'],
                    ],
                ],
            ],
            AIFunction::SaveCustomAnswer => [
                'type' => 'function',
                'function' => [
                    'name' => $function->value,
                    'description' => 'Сохранить ответ на текущий кастомный вопрос.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'step_id' => [
                                'type' => 'integer',
                                'description' => 'ID текущего шага',
                            ],
                            'extracted_value' => [
                                'type' => 'string',
                                'description' => 'Извлечённое значение. Для number — число строкой. Для choice — точный вариант из списка. Для yes_no — "yes" или "no". Для text — текст.',
                            ],
                        ],
                        'required' => ['step_id', 'extracted_value'],
                    ],
                ],
            ],
            AIFunction::CancelPipeline => [
                'type' => 'function',
                'function' => [
                    'name' => $function->value,
                    'description' => 'Клиент передумал записываться. Отменить текущий flow.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => new \stdClass,
                    ],
                ],
            ],
            AIFunction::EscalateToHuman => [
                'type' => 'function',
                'function' => [
                    'name' => $function->value,
                    'description' => 'Передать разговор живому оператору. Используй, когда не можешь помочь клиенту или клиент просит оператора.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => [
                                'type' => 'string',
                                'description' => 'Причина передачи оператору',
                            ],
                        ],
                    ],
                ],
            ],
        };
    }
}
