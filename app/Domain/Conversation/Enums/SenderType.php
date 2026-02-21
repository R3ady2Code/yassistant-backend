<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Enums;

enum SenderType: string
{
    case Client = 'client';
    case Bot = 'bot';
    case Operator = 'operator';
}
