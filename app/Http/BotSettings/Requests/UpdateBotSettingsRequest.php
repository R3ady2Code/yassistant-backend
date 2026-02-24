<?php

declare(strict_types=1);

namespace App\Http\BotSettings\Requests;

use App\Domain\AI\Enums\BotOperation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'operations' => ['sometimes', 'array'],
            'operations.*' => ['boolean'],
            'max_function_calls' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'greeting_message' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'escalation_message' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
