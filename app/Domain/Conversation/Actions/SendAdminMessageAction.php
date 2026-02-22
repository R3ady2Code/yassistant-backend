<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Conversation\Enums\MessageDirection;
use App\Domain\Conversation\Enums\MessageType;
use App\Domain\Conversation\Enums\SenderType;
use App\Domain\Conversation\Events\NewMessageEvent;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Models\Message;
use App\Domain\Identity\Contracts\VaultContract;

final class SendAdminMessageAction extends AbstractAction
{
    public function __construct(
        private readonly TelegramContract $telegram,
        private readonly VaultContract $vault,
    ) {
        parent::__construct();
    }

    public function handle(Conversation $conversation, string $text): Message
    {
        $channel = $conversation->channel;
        $botToken = $this->vault->get($channel->bot_token_vault_path);

        $this->telegram->sendMessage(
            $botToken,
            $conversation->external_chat_id,
            $text,
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'type' => MessageType::Text,
            'direction' => MessageDirection::Outgoing,
            'sender_type' => SenderType::Operator,
            'text' => $text,
        ]);

        $conversation->update(['last_message_at' => now()]);

        NewMessageEvent::dispatch($message, $conversation->tenant_id);

        return $message;
    }
}
