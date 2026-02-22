<?php

declare(strict_types=1);

namespace App\Http\Faq\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderFaqRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'uuid'],
        ];
    }
}
