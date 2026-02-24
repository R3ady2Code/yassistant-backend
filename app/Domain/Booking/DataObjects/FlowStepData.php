<?php

declare(strict_types=1);

namespace App\Domain\Booking\DataObjects;

use App\Domain\Booking\Enums\AnswerType;

final readonly class FlowStepData
{
    public function __construct(
        public string $questionText,
        public AnswerType $answerType,
        public bool $isRequired = true,
        public array $config = [],
        public int $sortOrder = 0,
    ) {}
}
