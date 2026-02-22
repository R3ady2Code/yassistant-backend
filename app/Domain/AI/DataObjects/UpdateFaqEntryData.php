<?php

declare(strict_types=1);

namespace App\Domain\AI\DataObjects;

final readonly class UpdateFaqEntryData
{
    public function __construct(
        public ?string $question = null,
        public ?string $answer = null,
        public ?bool $isActive = null,
    ) {}
}
