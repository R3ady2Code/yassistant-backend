<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Booking\Models\BookingFlow;
use App\Domain\Booking\Models\BookingFlowStep;

final class ReorderFlowStepsAction extends AbstractAction
{
    /**
     * @param int[] $stepIds Ordered list of step IDs
     */
    public function handle(BookingFlow $flow, array $stepIds): void
    {
        foreach ($stepIds as $index => $stepId) {
            BookingFlowStep::query()
                ->where('id', $stepId)
                ->where('flow_id', $flow->id)
                ->update(['sort_order' => $index]);
        }
    }
}
