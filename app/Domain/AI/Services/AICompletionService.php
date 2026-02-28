<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Contracts\OpenAIContract;
use App\Domain\AI\DataObjects\OperationResult;
use App\Domain\AI\Exceptions\InvalidClassificationException;

final class AICompletionService
{
    private const int MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly OpenAIContract $openAI,
        private readonly ResponseParser $responseParser,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $contextMessages
     */
    public function complete(string $model, string $systemPrompt, array $contextMessages): ?OperationResult
    {
        $tool = $this->responseParser->buildTool();

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$contextMessages,
        ];

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $response = $this->openAI->chatCompletion($model, $messages, [$tool]);

            try {
                return $this->responseParser->parse($response);
            } catch (InvalidClassificationException) {
                continue;
            }
        }

        return null;
    }
}
