<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Enums;

enum MessageDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
}
