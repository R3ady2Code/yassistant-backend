<?php

declare(strict_types=1);

namespace App\Domain\Conversation\DataObjects;

use App\Domain\Conversation\Models\Client;
use App\Domain\Conversation\Models\Conversation;

final readonly class HandleMessageData
{
    public function __construct(
        public string $botToken,
        public Conversation $conversation,
        public Client $client,
        public IncomingMessageData $messageData
    ) {}
}
