<?php

declare(strict_types=1);

namespace App\Domain\Channel\Enums;

enum ChannelStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
