<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Booking\DataObjects\BookingFlowData;
use App\Domain\Booking\Models\BookingFlow;
use Illuminate\Support\Facades\DB;

final class UpdateBookingFlowAction extends AbstractAction
{
    public function handle(BookingFlow $flow, BookingFlowData $data): BookingFlow
    {
        return DB::transaction(function () use ($flow, $data): BookingFlow {
            if ($data->isActive) {
                BookingFlow::where('tenant_id', $flow->tenant_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $flow->id)
                    ->update(['is_active' => false]);
            }

            $flow->update([
                'name' => $data->name,
                'ask_staff' => $data->askStaff,
                'is_active' => $data->isActive,
            ]);

            $flow->steps()->delete();

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
