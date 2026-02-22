<?php

declare(strict_types=1);

namespace App\Domain\AI\DataObjects;

final readonly class UpdateBotSettingsData
{
    public function __construct(
        public ?string $systemPrompt = null,
        public ?string $aiModel = null,
        public ?array $allowedOperations = null,
        public ?int $maxFunctionCalls = null,
        public ?string $greetingMessage = null,
        public ?string $escalationMessage = null,
    ) {}
}
