<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Events\ConversationModeChangedEvent;
use App\Domain\Conversation\Models\Conversation;

final class TakeoverConversationAction extends AbstractAction
{
    public function handle(Conversation $conversation): void
    {
        $conversation->update(['mode' => ConversationMode::Manual]);

        ConversationModeChangedEvent::dispatch(
            $conversation,
            $conversation->tenant_id,
            ConversationMode::Manual,
        );
    }
}
