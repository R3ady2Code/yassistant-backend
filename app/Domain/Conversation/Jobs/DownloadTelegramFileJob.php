<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Jobs;

use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Conversation\Contracts\FileStorageContract;
use App\Domain\Conversation\Models\Message;
use App\Domain\Identity\Contracts\VaultContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

final class DownloadTelegramFileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var int[] */
    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly string $messageId,
        private readonly string $fileId,
        private readonly string $botTokenVaultPath,
        private readonly string $storagePath,
    ) {}

    public function handle(
        VaultContract $vault,
        TelegramContract $telegram,
        FileStorageContract $fileStorage,
    ): void {
        $botToken = $vault->get($this->botTokenVaultPath);

        if ($botToken === null) {
            return;
        }

        $fileUrl = $telegram->getFileUrl($botToken, $this->fileId);
        $contents = Http::get($fileUrl)->body();

        $fileStorage->put($this->storagePath, $contents);

        $message = Message::find($this->messageId);

        if ($message !== null) {
            $metadata = $message->metadata ?? [];
            $metadata['file_path'] = $this->storagePath;
            $message->update(['metadata' => $metadata]);
        }
    }
}
