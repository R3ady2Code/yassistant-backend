<?php

declare(strict_types=1);

namespace App\Domain\Identity\DataObjects;

final readonly class UpdateYClientsTokenData
{
    public function __construct(
        public string $tenantId,
        public string $apiToken,
    ) {}
}
