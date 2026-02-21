<?php

declare(strict_types=1);

namespace App\Domain\Identity\DataObjects;

final readonly class GoogleLoginData
{
    public function __construct(
        public string $token,
    ) {}
}
