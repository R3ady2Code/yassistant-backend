<?php

declare(strict_types=1);

namespace App\Domain\Identity\DataObjects;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
