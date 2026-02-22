<?php

declare(strict_types=1);

namespace App\Http\BotSettings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBotSettingsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'system_prompt' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'ai_model' => ['sometimes', 'string', 'max:100'],
            'allowed_operations' => ['sometimes', 'array'],
            'allowed_operations.*' => ['string'],
            'max_function_calls' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'greeting_message' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'escalation_message' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
