<?php

declare(strict_types=1);

namespace App\Domain\Channel\Exceptions;

use RuntimeException;

final class BotTokenNotFoundException extends RuntimeException
{
    public function __construct(string $channelId)
    {
        parent::__construct("Bot token not found for channel [{$channelId}]");
    }
}
