<?php

declare(strict_types=1);

namespace App\Domain\Channel\DataObjects;

use App\Domain\Channel\Enums\ChannelType;

final readonly class CreateChannelData
{
    public function __construct(
        public string $tenantId,
        public ChannelType $type,
        public string $name,
        public string $botToken,
    ) {}
}
