<?php

declare(strict_types=1);

namespace App\Domain\Channel\Enums;

enum ChannelType: string
{
    case Telegram = 'telegram';
    case WhatsApp = 'whatsapp';
}
