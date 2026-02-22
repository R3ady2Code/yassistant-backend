<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Services;

use App\Domain\Conversation\Models\Message;
use App\Domain\Conversation\Enums\MessageType;

final class MessageTextPreparer
{
    public function prepare(Message $message): string
    {
        return match ($message->type) {
            MessageType::Text => $message->text ?? '',
            MessageType::Photo => $this->formatWithCaption('[Фото]', $message->text),
            MessageType::Voice => $this->prepareVoice($message),
            MessageType::Video => $this->formatWithCaption('[Видео]', $message->text),
            MessageType::Document => $this->prepareDocument($message),
            MessageType::Location => $this->prepareLocation($message),
            MessageType::Contact => $this->prepareContact($message),
            MessageType::Sticker => $this->prepareSticker($message),
            MessageType::CallbackQuery => $message->text ?? '',
        };
    }

    private function prepareVoice(Message $message): string
    {
        $transcription = $message->metadata['transcription'] ?? null;

        if ($transcription !== null) {
            return "[Голосовое сообщение] {$transcription}";
        }

        return '[Голосовое сообщение]';
    }

    private function prepareDocument(Message $message): string
    {
        $filename = $message->attachments['file_name'] ?? 'файл';

        return $this->formatWithCaption("[Документ: {$filename}]", $message->text);
    }

    private function prepareLocation(Message $message): string
    {
        $lat = $message->attachments['latitude'] ?? '?';
        $lon = $message->attachments['longitude'] ?? '?';

        return "[Геолокация: {$lat}, {$lon}]";
    }

    private function prepareContact(Message $message): string
    {
        $name = $message->attachments['first_name'] ?? '';
        $phone = $message->attachments['phone_number'] ?? '';

        return "[Контакт: {$name}, {$phone}]";
    }

    private function prepareSticker(Message $message): string
    {
        $emoji = $message->attachments['emoji'] ?? '';

        return "[Стикер: {$emoji}]";
    }

    private function formatWithCaption(string $prefix, ?string $caption): string
    {
        if ($caption !== null && $caption !== '') {
            return "{$prefix} {$caption}";
        }

        return $prefix;
    }
}
