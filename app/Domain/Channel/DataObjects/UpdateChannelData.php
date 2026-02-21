<?php

declare(strict_types=1);

namespace App\Domain\Channel\DataObjects;

final readonly class UpdateChannelData
{
    public function __construct(
        public string $name,
    ) {}
}
