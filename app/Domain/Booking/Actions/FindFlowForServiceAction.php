<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Booking\Models\BookingFlow;

final class FindFlowForServiceAction extends AbstractAction
{
    public function handle(string $tenantId, int $serviceId): ?BookingFlow
    {
        return BookingFlow::query()
            ->where('tenant_id', $tenantId)
            ->where('yclients_service_id', $serviceId)
            ->where('is_active', true)
            ->with('steps')
            ->first();
    }
}
