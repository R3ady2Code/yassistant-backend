<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Enums\AIFunction;
use App\Domain\Conversation\Models\Conversation;

final class FunctionExecutor
{
    public const string ESCALATE_MARKER = '__ESCALATE__';

    /**
     * @param  array<string, mixed>  $args
     */
    public function execute(AIFunction $function, array $args, Conversation $conversation): string
    {
        return match ($function) {
            AIFunction::EscalateToHuman => self::ESCALATE_MARKER,
            default => json_encode(
                ['error' => 'Функция временно недоступна'],
                JSON_UNESCAPED_UNICODE,
            ),
        };
    }
}
