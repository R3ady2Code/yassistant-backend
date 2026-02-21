<?php

declare(strict_types=1);

namespace App\Adapters\Telegram;

use App\Domain\Conversation\DataObjects\IncomingMessageData;
use App\Domain\Conversation\Enums\MessageType;
use SergiX44\Hydrator\Hydrator;
use SergiX44\Nutgram\Telegram\Types\Common\Update;
use SergiX44\Nutgram\Telegram\Types\Inline\CallbackQuery;
use SergiX44\Nutgram\Telegram\Types\Message\Message;
use SergiX44\Nutgram\Telegram\Types\User\User;

class TelegramUpdateParser
{
    private readonly Hydrator $hydrator;

    public function __construct()
    {
        $this->hydrator = new Hydrator;
    }

    public function parse(string $channelId, array $rawUpdate): ?IncomingMessageData
    {
        $update = $this->hydrator->hydrate(Update::class, $rawUpdate);

        if ($update->callback_query !== null) {
            return $this->parseCallbackQuery($channelId, $update->callback_query);
        }

        $message = $update->message ?? $update->edited_message;
        if ($message === null) {
            return null;
        }

        return $this->parseMessage($channelId, $message);
    }

    private function parseMessage(string $channelId, Message $msg): ?IncomingMessageData
    {
        if ($msg->from === null) {
            return null;
        }

        $chatId = (string) $msg->chat->id;
        $userId = (string) $msg->from->id;
        $senderName = $this->buildSenderName($msg->from);

        if (is_array($msg->photo) && count($msg->photo) > 0) {
            return $this->buildData($channelId, $chatId, $userId, $senderName, MessageType::Photo, $msg->caption, [
                'file_id' => $msg->photo[count($msg->photo) - 1]->file_id,
                'width' => $msg->photo[count($msg->photo) - 1]->width,
                'height' => $msg->photo[count($msg->photo) - 1]->height,
            ]);
        }

        if ($msg->voice !== null) {
            return $this->buildData($channelId, $chatId, $userId, $senderName, MessageType::Voice, $msg->caption, [
                'file_id' => $msg->voice->file_id,
                'duration' => $msg->voice->duration,
            ]);
        }

        if ($msg->video !== null) {
            return $this->buildData($channelId, $chatId, $userId, $senderName, MessageType::Video, $msg->caption, [
                'file_id' => $msg->video->file_id,
                'duration' => $msg->video->duration,
            ]);
        }

        if ($msg->document !== null) {
            return $this->buildData($channelId, $chatId, $userId, $senderName, MessageType::Document, $msg->caption, [
                'file_id' => $msg->document->file_id,
                'file_name' => $msg->document->file_name ?? 'unknown',
                'mime_type' => $msg->document->mime_type ?? null,
            ]);
        }

        if ($msg->location !== null) {
            return $this->buildData($channelId, $chatId, $userId, $senderName, MessageType::Location, null, [
                'latitude' => $msg->location->latitude,
                'longitude' => $msg->location->longitude,
            ]);
        }

        if ($msg->contact !== null) {
            return $this->buildData($channelId, $chatId, $userId, $senderName, MessageType::Contact, null, [
                'phone_number' => $msg->contact->phone_number,
                'first_name' => $msg->contact->first_name,
                'last_name' => $msg->contact->last_name ?? null,
            ]);
        }

        if ($msg->sticker !== null) {
            return $this->buildData($channelId, $chatId, $userId, $senderName, MessageType::Sticker, $msg->sticker->emoji ?? null, [
                'file_id' => $msg->sticker->file_id,
                'emoji' => $msg->sticker->emoji ?? null,
            ]);
        }

        if ($msg->text !== null) {
            return $this->buildData($channelId, $chatId, $userId, $senderName, MessageType::Text, $msg->text, null);
        }

        return null;
    }

    private function parseCallbackQuery(string $channelId, CallbackQuery $cbq): ?IncomingMessageData
    {
        if ($cbq->message === null || $cbq->from === null) {
            return null;
        }

        $chatId = (string) $cbq->message->chat->id;
        $userId = (string) $cbq->from->id;
        $senderName = $this->buildSenderName($cbq->from);

        return $this->buildData($channelId, $chatId, $userId, $senderName, MessageType::CallbackQuery, $cbq->data ?? null, [
            'callback_data' => $cbq->data ?? '',
            'message_id' => (string) $cbq->message->message_id,
        ], callbackQueryId: $cbq->id);
    }

    private function buildSenderName(?User $from): string
    {
        if ($from === null) {
            return 'Unknown';
        }

        $parts = [$from->first_name];
        if ($from->last_name) {
            $parts[] = $from->last_name;
        }

        return implode(' ', $parts);
    }

    private function buildData(
        string $channelId,
        string $chatId,
        string $userId,
        string $senderName,
        MessageType $type,
        ?string $text,
        ?array $attachments,
        ?string $callbackQueryId = null,
    ): IncomingMessageData {
        return new IncomingMessageData(
            channelId: $channelId,
            externalChatId: $chatId,
            externalUserId: $userId,
            type: $type,
            text: $text,
            senderName: $senderName,
            senderPhone: null,
            attachments: $attachments,
            callbackQueryId: $callbackQueryId,
        );
    }
}
