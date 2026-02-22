<?php

declare(strict_types=1);

namespace App\Adapters\Vault;

use App\Domain\Identity\Contracts\VaultContract;
use Illuminate\Support\Facades\Cache;

readonly class FakeVaultAdapter implements VaultContract
{
    private const string PREFIX = 'vault:';

    public function get(string $path): ?string
    {
        $value = Cache::get(self::PREFIX.$path);

        return is_string($value) ? $value : null;
    }

    public function put(string $path, string $value): void
    {
        Cache::forever(self::PREFIX.$path, $value);
    }

    public function delete(string $path): void
    {
        Cache::forget(self::PREFIX.$path);
    }

    public function has(string $path): bool
    {
        return Cache::has(self::PREFIX.$path);
    }
}
