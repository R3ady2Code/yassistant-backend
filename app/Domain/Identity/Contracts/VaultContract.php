<?php

declare(strict_types=1);

namespace App\Domain\Identity\Contracts;

interface VaultContract
{
    public function get(string $path): ?string;

    public function put(string $path, string $value): void;

    public function delete(string $path): void;

    public function has(string $path): bool;
}
