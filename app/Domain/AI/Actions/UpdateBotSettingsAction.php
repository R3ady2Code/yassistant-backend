<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\DataObjects\UpdateBotSettingsData;
use App\Domain\AI\Models\BotSettings;

final class UpdateBotSettingsAction extends AbstractAction
{
    public function handle(BotSettings $settings, UpdateBotSettingsData $data): BotSettings
    {
        $fields = [];

        if ($data->systemPrompt !== null) {
            $fields['system_prompt'] = $data->systemPrompt;
        }

        if ($data->aiModel !== null) {
            $fields['ai_model'] = $data->aiModel;
        }

        if ($data->allowedOperations !== null) {
            $fields['allowed_operations'] = $data->allowedOperations;
        }

        if ($data->maxFunctionCalls !== null) {
            $fields['max_function_calls'] = $data->maxFunctionCalls;
        }

        if ($data->greetingMessage !== null) {
            $fields['greeting_message'] = $data->greetingMessage;
        }

        if ($data->escalationMessage !== null) {
            $fields['escalation_message'] = $data->escalationMessage;
        }

        if ($fields !== []) {
            $settings->update($fields);
        }

        return $settings->refresh();
    }
}
