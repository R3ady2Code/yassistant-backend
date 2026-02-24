<?php

declare(strict_types=1);

namespace App\Domain\AI\DataObjects;

final readonly class UpdateBotSettingsData
{
    /**
     * @param ?array<string, bool> $operations Map of operation => is_enabled
     */
    public function __construct(
        public ?string $systemPrompt = null,
        public ?string $aiModel = null,
        public ?array $operations = null,
        public ?int $maxFunctionCalls = null,
        public ?string $greetingMessage = null,
        public ?string $escalationMessage = null,
    ) {}
}
