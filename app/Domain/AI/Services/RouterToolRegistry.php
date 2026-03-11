<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Enums\BotOperation;
use App\Domain\AI\Enums\RouterTool;
use App\Domain\AI\Models\BotSettings;

final class RouterToolRegistry
{
    /**
     * @return array<int, array{type: string, function: array<string, mixed>}>
     */
    public function buildTools(BotSettings $settings): array
    {
        $tools = [
            $this->buildSendResponse(),
            $this->buildEscalateToHuman(),
        ];

        $enabledOperations = $settings->operations
            ->where('is_enabled', true)
            ->pluck('operation');

        foreach ($enabledOperations as $operation) {
            $tool = $this->buildOperationTool($operation);

            if ($tool !== null) {
                $tools[] = $tool;
            }
        }

        return $tools;
    }

    /**
     * @return array{type: string, function: array<string, mixed>}
     */
    private function buildSendResponse(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => RouterTool::SendResponse->value,
                'description' => 'Отправить текстовый ответ клиенту',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'text' => [
                            'type' => 'string',
                            'description' => 'Текст ответа клиенту',
                        ],
                    ],
                    'required' => ['text'],
                ],
            ],
        ];
    }

    /**
     * @return array{type: string, function: array<string, mixed>}
     */
    private function buildEscalateToHuman(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => RouterTool::EscalateToHuman->value,
                'description' => 'Передать диалог оператору (когда клиент просит оператора или бот не может помочь)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ];
    }

    /**
     * @return array{type: string, function: array<string, mixed>}|null
     */
    private function buildOperationTool(BotOperation $operation): ?array
    {
        return match ($operation) {
            BotOperation::CreateBooking => [
                'type' => 'function',
                'function' => [
                    'name' => RouterTool::CreateBooking->value,
                    'description' => 'Клиент хочет записаться на услугу. Вызови эту функцию для начала процесса записи.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
            BotOperation::CancelBooking => [
                'type' => 'function',
                'function' => [
                    'name' => RouterTool::CancelBooking->value,
                    'description' => 'Клиент хочет отменить существующую запись.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
            BotOperation::EditBooking => [
                'type' => 'function',
                'function' => [
                    'name' => RouterTool::EditBooking->value,
                    'description' => 'Клиент хочет изменить существующую запись.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
            BotOperation::AskFaq => null,
        };
    }
}
