<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Models\BotSettings;
use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Enums\MessageDirection;
use App\Domain\Conversation\Enums\MessageType;
use App\Domain\Conversation\Enums\SenderType;
use App\Domain\Conversation\Events\ConversationEscalatedEvent;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Models\Message;

final class EscalateConversationAction extends AbstractAction
{
    private const string DEFAULT_ESCALATION_MESSAGE = 'Сейчас я переведу вас на оператора. Пожалуйста, подождите.';

    public function __construct(
        private readonly TelegramContract $telegram,
    ) {
        parent::__construct();
    }

    public function handle(Conversation $conversation, string $botToken): void
    {
        $conversation->update(['mode' => ConversationMode::Escalated]);

        $settings = BotSettings::where('tenant_id', $conversation->tenant_id)->first();
        $escalationMessage = $settings?->escalation_message ?? self::DEFAULT_ESCALATION_MESSAGE;

        $this->telegram->sendMessage(
            $botToken,
            $conversation->external_chat_id,
            $escalationMessage,
        );

        Message::create([
            'conversation_id' => $conversation->id,
            'type' => MessageType::Text,
            'direction' => MessageDirection::Outgoing,
            'sender_type' => SenderType::Bot,
            'text' => $escalationMessage,
        ]);

        $conversation->update(['last_message_at' => now()]);

        ConversationEscalatedEvent::dispatch($conversation, $conversation->tenant_id);
    }
}
