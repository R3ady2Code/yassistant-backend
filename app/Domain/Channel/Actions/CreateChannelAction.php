<?php

declare(strict_types=1);

namespace App\Domain\Channel\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Channel\DataObjects\CreateChannelData;
use App\Domain\Channel\Enums\ChannelStatus;
use App\Domain\Channel\Models\Channel;
use App\Domain\Identity\Contracts\VaultContract;

final class CreateChannelAction extends AbstractAction
{
    public function __construct(
        private readonly VaultContract $vault,
    ) {
        parent::__construct();
    }

    public function handle(CreateChannelData $data): Channel
    {
        $vaultPath = "tenants/{$data->tenantId}/channels/".uniqid('', true).'/bot_token';

        $this->vault->put($vaultPath, $data->botToken);

        return Channel::create([
            'tenant_id' => $data->tenantId,
            'type' => $data->type,
            'name' => $data->name,
            'bot_token_vault_path' => $vaultPath,
            'status' => ChannelStatus::Inactive,
        ]);
    }
}
