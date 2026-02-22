<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Events\ConversationModeChangedEvent;
use App\Domain\Conversation\Models\Conversation;

final class ToggleAIModeAction extends AbstractAction
{
    public function handle(Conversation $conversation): ConversationMode
    {
        $newMode = $conversation->mode === ConversationMode::AI
            ? ConversationMode::Manual
            : ConversationMode::AI;

        $conversation->update(['mode' => $newMode]);

        ConversationModeChangedEvent::dispatch(
            $conversation,
            $conversation->tenant_id,
            $newMode,
        );

        return $newMode;
    }
}
