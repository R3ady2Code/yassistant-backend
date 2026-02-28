<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Booking\Models\BookingFlowStep;
use App\Domain\Booking\Services\FlowStepValidator;

final class ValidateFlowStepAnswerAction extends AbstractAction
{
    public function __construct(
        private readonly FlowStepValidator $validator,
    ) {
        parent::__construct();
    }

    public function handle(BookingFlowStep $step, string $extracted): int|string|bool|null
    {
        return $this->validator->validate($step, $extracted);
    }
}
