<?php

declare(strict_types=1);

namespace App\Domain\AI\DataObjects;

final readonly class CreateFaqEntryData
{
    public function __construct(
        public string $tenantId,
        public string $question,
        public string $answer,
    ) {}
}
