<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Booking\Models\BookingFlow;
use Illuminate\Support\Facades\DB;

final class ToggleBookingFlowAction extends AbstractAction
{
    public function handle(BookingFlow $flow): BookingFlow
    {
        return DB::transaction(function () use ($flow): BookingFlow {
            $newState = ! $flow->is_active;

            if ($newState) {
                BookingFlow::where('tenant_id', $flow->tenant_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $flow->id)
                    ->update(['is_active' => false]);
            }

            $flow->update(['is_active' => $newState]);

            return $flow->load('steps');
        });
    }
}
