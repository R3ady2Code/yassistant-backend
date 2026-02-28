<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Contracts\OpenAIContract;
use App\Domain\AI\Enums\BotOperation;
use App\Domain\AI\Exceptions\InvalidClassificationException;
use App\Domain\AI\Models\BotSettingOperation;
use App\Domain\AI\Models\BotSettings;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Services\ConversationContextLoader;

final class GetBotOperationAction extends AbstractAction
{
    private const int MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly OpenAIContract $openAI,
        private readonly ConversationContextLoader $contextLoader,
    ) {
        parent::__construct();
    }

    public function handle(BotSettings $settings, Conversation $conversation): ?BotOperation
    {
        $allowedOperations = BotSettingOperation::query()
            ->where('bot_setting_id', $settings->id)
            ->where('is_enabled', true)
            ->pluck('operation')
            ->map(fn (BotOperation $op) => $op->value)
            ->all();

        if (empty($allowedOperations)) {
            return null;
        }

        $systemPrompt = $this->buildSystemPrompt($allowedOperations);
        $contextMessages = $this->contextLoader->load($conversation);
        $tool = $this->buildTool($allowedOperations);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$contextMessages,
        ];

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $response = $this->openAI->chatCompletion($settings->ai_model, $messages, [$tool]);

            try {
                return $this->parseResponse($response, $allowedOperations);
            } catch (InvalidClassificationException) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  string[]  $allowedOperations
     */
    private function buildSystemPrompt(array $allowedOperations): string
    {
        $lines = [
            'Определи, какую операцию хочет выполнить пользователь.',
            '',
            'Доступные операции:',
        ];

        foreach ($allowedOperations as $value) {
            $lines[] = "- `{$value}`: {$this->describeOperation(BotOperation::from($value))}";
        }

        $lines[] = '';
        $lines[] = 'Если сообщение не относится ни к одной операции — вызови select_operation без параметра operation.';

        return implode("\n", $lines);
    }

    /**
     * @param  string[]  $allowedOperations
     * @return array{type: string, function: array<string, mixed>}
     */
    private function buildTool(array $allowedOperations): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'select_operation',
                'description' => 'Выбрать операцию на основе сообщения пользователя',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'operation' => [
                            'type' => 'string',
                            'enum' => array_values($allowedOperations),
                            'description' => 'Определённая операция',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  string[]  $allowedOperations
     *
     * @throws InvalidClassificationException
     */
    private function parseResponse(array $response, array $allowedOperations): ?BotOperation
    {
        if ($response['tool_calls'] === null) {
            throw new InvalidClassificationException('No tool call in response');
        }

        foreach ($response['tool_calls'] as $toolCall) {
            if ($toolCall['function']['name'] !== 'select_operation') {
                continue;
            }

            $args = json_decode($toolCall['function']['arguments'], true) ?? [];

            if (! isset($args['operation'])) {
                return null;
            }

            $operation = BotOperation::tryFrom($args['operation']);

            if ($operation === null || ! in_array($args['operation'], $allowedOperations, true)) {
                throw new InvalidClassificationException("Unknown operation: {$args['operation']}");
            }

            return $operation;
        }

        throw new InvalidClassificationException('select_operation not called');
    }

    private function describeOperation(BotOperation $operation): string
    {
        return match ($operation) {
            BotOperation::CreateBooking => 'Создание записи на услугу',
            BotOperation::CancelBooking => 'Отмена существующей записи',
            BotOperation::EditBooking => 'Изменение существующей записи',
            BotOperation::AskFaq => 'Ответ на часто задаваемый вопрос',
        };
    }
}
