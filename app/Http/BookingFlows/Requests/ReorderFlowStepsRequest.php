<?php

declare(strict_types=1);

namespace App\Http\BookingFlows\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderFlowStepsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
        ];
    }
}
