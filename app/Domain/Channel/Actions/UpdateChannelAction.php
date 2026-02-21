<?php

declare(strict_types=1);

namespace App\Domain\Channel\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Channel\DataObjects\UpdateChannelData;
use App\Domain\Channel\Models\Channel;

final class UpdateChannelAction extends AbstractAction
{
    public function handle(Channel $channel, UpdateChannelData $data): Channel
    {
        $channel->update([
            'name' => $data->name,
        ]);

        return $channel->refresh();
    }
}
