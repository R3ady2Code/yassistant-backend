<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Enums;

enum ConversationStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
