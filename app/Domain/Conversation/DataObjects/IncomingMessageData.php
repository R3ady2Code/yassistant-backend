<?php

declare(strict_types=1);

namespace App\Domain\Conversation\DataObjects;

use App\Domain\Conversation\Enums\MessageType;

final readonly class IncomingMessageData
{
    public function __construct(
        public string $channelId,
        public string $externalChatId,
        public MessageType $type,
        public ?string $text,
        public ?string $senderName,
        public ?string $senderPhone,
        public ?array $attachments,
    ) {}
}
