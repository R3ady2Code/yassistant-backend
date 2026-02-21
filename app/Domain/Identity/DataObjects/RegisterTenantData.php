<?php

declare(strict_types=1);

namespace App\Domain\Identity\DataObjects;

final readonly class RegisterTenantData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $tenantName,
    ) {}
}
