<?php

declare(strict_types=1);

namespace App\Adapters\OpenAI;

use App\Domain\AI\Contracts\OpenAIContract;
use OpenAI\Client;

readonly class OpenAIAdapter implements OpenAIContract
{
    public function __construct(
        private Client $client,
    ) {}

    public function chatCompletion(string $model, array $messages, array $tools = []): array
    {
        $params = [
            'model' => $model,
            'messages' => $messages,
        ];

        if ($tools !== []) {
            $params['tools'] = $tools;
        }

        $response = $this->client->chat()->create($params);

        $choice = $response->choices[0];

        $toolCalls = null;
        if ($choice->message->toolCalls !== []) {
            $toolCalls = array_map(
                fn ($toolCall) => [
                    'id' => $toolCall->id,
                    'function' => [
                        'name' => $toolCall->function->name,
                        'arguments' => $toolCall->function->arguments,
                    ],
                ],
                $choice->message->toolCalls,
            );
        }

        return [
            'content' => $choice->message->content,
            'tool_calls' => $toolCalls,
            'finish_reason' => $choice->finishReason ?? 'stop',
        ];
    }
}
