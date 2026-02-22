<?php

declare(strict_types=1);

namespace App\Http\Faq\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFaqRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:2000'],
            'answer' => ['required', 'string', 'max:5000'],
        ];
    }
}
