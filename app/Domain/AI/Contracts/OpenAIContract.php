<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

interface OpenAIContract
{
    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @return array{content: ?string, tool_calls: ?array<int, array<string, mixed>>, finish_reason: string}
     */
    public function chatCompletion(string $model, array $messages, array $tools = []): array;
}
