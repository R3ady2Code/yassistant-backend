<?php

declare(strict_types=1);

namespace App\Domain\Channel\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Channel\Models\Channel;
use App\Domain\Identity\Contracts\VaultContract;

final class DeactivateChannelAction extends AbstractAction
{
    public function __construct(
        private readonly VaultContract $vault,
        private readonly TelegramContract $webhookRegistrar,
    ) {
        parent::__construct();
    }

    public function handle(Channel $channel): Channel
    {
        if (!$channel->is_active) {
            return $channel;
        }

        $botToken = $this->vault->get($channel->bot_token_vault_path);

        if ($botToken) {
            $this->webhookRegistrar->unregisterWebhook($channel, $botToken);
        }

        $channel->update([
            'is_active' => false,
            'webhook_url' => null,
            'webhook_secret' => null,
        ]);

        return $channel->refresh();
    }
}
