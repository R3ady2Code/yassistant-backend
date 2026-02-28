<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\DataObjects\OperationResult;
use App\Domain\AI\Exceptions\InvalidClassificationException;
use App\Domain\Conversation\Enums\ConversationMode;

final class ResponseParser
{
    private const string TOOL_NAME = 'send_response';

    /**
     * @return array{type: string, function: array<string, mixed>}
     */
    public function buildTool(): array
    {
        $modeValues = array_map(
            fn (ConversationMode $m) => $m->value,
            ConversationMode::cases(),
        );

        return [
            'type' => 'function',
            'function' => [
                'name' => self::TOOL_NAME,
                'description' => 'Отправить ответ пользователю и определить режим диалога',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'mode' => [
                            'type' => 'string',
                            'enum' => $modeValues,
                            'description' => 'ai — бот продолжает вести диалог, escalated — передать оператору, manual — оператор уже ведёт',
                        ],
                        'text' => [
                            'type' => 'string',
                            'description' => 'Текст ответа пользователю',
                        ],
                    ],
                    'required' => ['mode', 'text'],
                ],
            ],
        ];
    }

    /**
     * @param  array{content: ?string, tool_calls: ?array<int, array<string, mixed>>, finish_reason: string}  $response
     *
     * @throws InvalidClassificationException
     */
    public function parse(array $response): OperationResult
    {
        if ($response['tool_calls'] === null) {
            throw new InvalidClassificationException('No tool call in response');
        }

        foreach ($response['tool_calls'] as $toolCall) {
            if ($toolCall['function']['name'] !== self::TOOL_NAME) {
                continue;
            }

            $args = json_decode($toolCall['function']['arguments'], true) ?? [];

            $mode = ConversationMode::tryFrom($args['mode'] ?? '');
            $text = $args['text'] ?? null;

            if ($mode === null || $text === null || $text === '') {
                throw new InvalidClassificationException("Invalid send_response args: mode={$args['mode']}, text empty");
            }

            return new OperationResult(
                mode: $mode,
                responseText: $text,
            );
        }

        throw new InvalidClassificationException('send_response not called');
    }
}
