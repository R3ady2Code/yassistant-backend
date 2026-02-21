<?php

declare(strict_types=1);

namespace App\Domain\Identity\DataObjects;

use App\Domain\Identity\Models\User;

final readonly class AuthResultData
{
    public function __construct(
        public User $user,
        public string $token,
    ) {}
}
