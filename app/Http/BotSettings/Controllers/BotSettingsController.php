<?php

declare(strict_types=1);

namespace App\Http\BotSettings\Controllers;

use App\Abstracts\AbstractController;
use App\Domain\AI\Actions\UpdateBotSettingsAction;
use App\Domain\AI\DataObjects\UpdateBotSettingsData;
use App\Domain\AI\Models\BotSettings;
use App\Http\BotSettings\Requests\UpdateBotSettingsRequest;
use App\Http\BotSettings\Resources\BotSettingsResource;
use Illuminate\Http\JsonResponse;

final class BotSettingsController extends AbstractController
{
    public function show(): JsonResponse
    {
        $settings = BotSettings::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        return response()->json(new BotSettingsResource($settings));
    }

    public function update(
        UpdateBotSettingsRequest $request,
        UpdateBotSettingsAction $action,
    ): JsonResponse {
        $settings = BotSettings::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $data = new UpdateBotSettingsData(
            systemPrompt: $request->validated('system_prompt'),
            aiModel: $request->validated('ai_model'),
            allowedOperations: $request->validated('allowed_operations'),
            maxFunctionCalls: $request->validated('max_function_calls'),
            greetingMessage: $request->validated('greeting_message'),
            escalationMessage: $request->validated('escalation_message'),
        );

        $result = $action->handle($settings, $data);

        return response()->json(new BotSettingsResource($result));
    }
}
