<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Enums\AIFunction;
use App\Domain\AI\Models\BotSettings;

final class FunctionRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forTenant(BotSettings $settings): array
    {
        $tools = [];
        $allowedOperations = $settings->allowed_operations ?? [];

        foreach (AIFunction::cases() as $function) {
            if ($function === AIFunction::EscalateToHuman) {
                $tools[] = $this->buildDefinition($function);

                continue;
            }

            if (in_array($function->value, $allowedOperations, true)) {
                $tools[] = $this->buildDefinition($function);
            }
        }

        return $tools;
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
