<?php

declare(strict_types=1);

namespace App\Domain\Channel\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Channel\Enums\ChannelStatus;
use App\Domain\Channel\Exceptions\BotTokenNotFoundException;
use App\Domain\Channel\Models\Channel;
use App\Domain\Identity\Contracts\VaultContract;

final class ActivateChannelAction extends AbstractAction
{
    public function __construct(
        private readonly VaultContract $vault,
        private readonly TelegramContract $webhookRegistrar,
    ) {
        parent::__construct();
    }

    public function handle(Channel $channel): Channel
    {
        if ($channel->status === ChannelStatus::Active) {
            return $channel;
        }

        $botToken = $this->vault->get($channel->bot_token_vault_path);

        if (! $botToken) {
            throw new BotTokenNotFoundException($channel->id);
        }

        $webhookSecret = bin2hex(random_bytes(32));

        $webhookUrl = $this->webhookRegistrar->registerWebhook($channel, $botToken, $webhookSecret);

        $channel->update([
            'status' => ChannelStatus::Active,
            'webhook_url' => $webhookUrl,
            'webhook_secret' => $webhookSecret,
        ]);

        return $channel->refresh();
    }
}
