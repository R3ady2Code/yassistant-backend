<?php

declare(strict_types=1);

namespace App\Http\BotSettings\Resources;

use App\Abstracts\AbstractJsonResource;
use App\Domain\AI\Models\BotSettings;
use Illuminate\Http\Request;

/**
 * @mixin BotSettings
 */
class BotSettingsResource extends AbstractJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'system_prompt' => $this->system_prompt,
            'ai_model' => $this->ai_model,
            'allowed_operations' => $this->allowed_operations,
            'max_function_calls' => $this->max_function_calls,
            'greeting_message' => $this->greeting_message,
            'escalation_message' => $this->escalation_message,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
