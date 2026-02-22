<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Conversation\DataObjects\HandleMessageData;
use App\Domain\Conversation\Enums\MessageDirection;
use App\Domain\Conversation\Enums\SenderType;
use App\Domain\Conversation\Events\NewMessageEvent;
use App\Domain\Conversation\Models\Message;

final class ProcessIncomingMessageAction extends AbstractAction
{
    public function handle(HandleMessageData $data): Message
    {
        $message = Message::create([
            'conversation_id' => $data->conversation->id,
            'type' => $data->messageData->type,
            'direction' => MessageDirection::Incoming,
            'sender_type' => SenderType::Client,
            'text' => $data->messageData->text,
            'attachments' => $data->messageData->attachments,
        ]);

        $data->conversation->update(['last_message_at' => now()]);

        NewMessageEvent::dispatch($message, $data->conversation->tenant_id);

        return $message;
    }
}
