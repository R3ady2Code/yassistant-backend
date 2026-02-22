<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Conversation\DataObjects\HandleMessageData;
use App\Domain\Conversation\Enums\MessageDirection;
use App\Domain\Conversation\Enums\SenderType;
use App\Domain\Conversation\Events\NewMessageEvent;
use App\Domain\Conversation\Jobs\DownloadTelegramFileJob;
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

        $this->dispatchFileDownload($message, $data);

        NewMessageEvent::dispatch($message, $data->conversation->tenant_id);

        return $message;
    }

    private function dispatchFileDownload(Message $message, HandleMessageData $data): void
    {
        $fileId = $data->messageData->attachments['file_id'] ?? null;

        if ($fileId === null) {
            return;
        }

        $filename = $data->messageData->attachments['file_name'] ?? $fileId;
        $storagePath = "messages/{$message->id}/{$filename}";

        $channel = $data->conversation->channel;

        if ($channel->bot_token_vault_path === null) {
            return;
        }

        DownloadTelegramFileJob::dispatch(
            $message->id,
            $fileId,
            $channel->bot_token_vault_path,
            $storagePath,
        );
    }
}
