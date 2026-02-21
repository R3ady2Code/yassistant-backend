<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\FileStorage\FileStorageAdapter;
use App\Adapters\Telegram\TelegramAdapter;
use App\Adapters\Vault\FakeVaultAdapter;
use App\Adapters\Vault\VaultAdapter;
use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Conversation\Contracts\FileStorageContract;
use App\Domain\Identity\Contracts\VaultContract;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            VaultContract::class,
            $this->app->environment('production') ? VaultAdapter::class : FakeVaultAdapter::class,
        );

        $this->app->bind(FileStorageContract::class, FileStorageAdapter::class);

        $this->app->bind(TelegramContract::class, TelegramAdapter::class);
    }

    public function boot(): void
    {
        //
    }
}
