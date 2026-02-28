<?php

declare(strict_types=1);

namespace App\Domain\Booking\DataObjects;

final readonly class BookingFlowData
{
    /**
     * @param FlowStepData[] $steps
     */
    public function __construct(
        public string $tenantId,
        public string $name,
        public bool $askStaff = false,
        public bool $isActive = true,
        public array $steps = [],
    ) {}
}
