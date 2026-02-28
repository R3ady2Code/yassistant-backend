<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Booking\DataObjects\BookingFlowData;
use App\Domain\Booking\Models\BookingFlow;
use Illuminate\Support\Facades\DB;

final class CreateBookingFlowAction extends AbstractAction
{
    public function handle(BookingFlowData $data): BookingFlow
    {
        return DB::transaction(function () use ($data): BookingFlow {
            if ($data->isActive) {
                BookingFlow::where('tenant_id', $data->tenantId)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $flow = BookingFlow::create([
                'tenant_id' => $data->tenantId,
                'name' => $data->name,
                'ask_staff' => $data->askStaff,
                'is_active' => $data->isActive,
            ]);

            foreach ($data->steps as $index => $stepData) {
                $flow->steps()->create([
                    'question_text' => $stepData->questionText,
                    'answer_type' => $stepData->answerType,
                    'is_required' => $stepData->isRequired,
                    'config' => $stepData->config,
                    'sort_order' => $stepData->sortOrder ?: $index,
                ]);
            }

            return $flow->load('steps');
        });
    }
}
