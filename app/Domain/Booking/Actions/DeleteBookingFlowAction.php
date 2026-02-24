<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Booking\Models\BookingFlow;

final class DeleteBookingFlowAction extends AbstractAction
{
    public function handle(BookingFlow $flow): void
    {
        $flow->delete();
    }
}
