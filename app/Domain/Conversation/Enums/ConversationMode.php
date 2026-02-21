<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Enums;

enum ConversationMode: string
{
    case AI = 'ai';
    case Manual = 'manual';
}
