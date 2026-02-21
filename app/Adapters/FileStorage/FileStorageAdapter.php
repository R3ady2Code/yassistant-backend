<?php

declare(strict_types=1);

namespace App\Adapters\FileStorage;

use App\Domain\Conversation\Contracts\FileStorageContract;
use Illuminate\Support\Facades\Storage;

readonly class FileStorageAdapter implements FileStorageContract
{
    public function put(string $path, string $contents): string
    {
        Storage::put($path, $contents);

        return $path;
    }

    public function get(string $path): ?string
    {
        if (! Storage::exists($path)) {
            return null;
        }

        return Storage::get($path);
    }

    public function delete(string $path): bool
    {
        return Storage::delete($path);
    }

    public function url(string $path): string
    {
        return Storage::url($path);
    }

    public function exists(string $path): bool
    {
        return Storage::exists($path);
    }
}
