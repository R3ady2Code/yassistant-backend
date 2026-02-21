<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Contracts;

interface FileStorageContract
{
    public function put(string $path, string $contents): string;

    public function get(string $path): ?string;

    public function delete(string $path): bool;

    public function url(string $path): string;

    public function exists(string $path): bool;
}
