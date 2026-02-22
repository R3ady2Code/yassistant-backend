<?php

declare(strict_types=1);

namespace App\Adapters\Vault;

use App\Domain\Identity\Contracts\VaultContract;
use Illuminate\Support\Facades\Http;
use RuntimeException;

readonly class VaultAdapter implements VaultContract
{
    public function get(string $path): ?string
    {
        $response = Http::withToken(config('services.vault.token'))
            ->get(config('services.vault.url')."/v1/secret/data/{$path}");

        if ($response->notFound()) {
            return null;
        }

        if ($response->failed()) {
            throw new RuntimeException("Vault GET failed for path [{$path}]: {$response->body()}");
        }

        return $response->json('data.data.value');
    }

    public function put(string $path, string $value): void
    {
        $response = Http::withToken(config('services.vault.token'))
            ->post(config('services.vault.url')."/v1/secret/data/{$path}", [
                'data' => ['value' => $value],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Vault PUT failed for path [{$path}]: {$response->body()}");
        }
    }

    public function delete(string $path): void
    {
        $response = Http::withToken(config('services.vault.token'))
            ->delete(config('services.vault.url')."/v1/secret/data/{$path}");

        if ($response->failed()) {
            throw new RuntimeException("Vault DELETE failed for path [{$path}]: {$response->body()}");
        }
    }

    public function has(string $path): bool
    {
        return $this->get($path) !== null;
    }
}
