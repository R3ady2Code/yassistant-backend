<?php

declare(strict_types=1);

namespace App\Http\BookingFlows\Requests;

use App\Domain\Booking\Enums\AnswerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBookingFlowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'yclients_service_id' => ['required', 'integer'],
            'yclients_service_name' => ['required', 'string', 'max:255'],
            'yclients_branch_id' => ['required', 'integer'],
            'ask_staff' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'steps' => ['sometimes', 'array'],
            'steps.*.question_text' => ['required', 'string', 'max:500'],
            'steps.*.answer_type' => ['required', 'string', Rule::enum(AnswerType::class)],
            'steps.*.is_required' => ['sometimes', 'boolean'],
            'steps.*.config' => ['sometimes', 'array'],
            'steps.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
