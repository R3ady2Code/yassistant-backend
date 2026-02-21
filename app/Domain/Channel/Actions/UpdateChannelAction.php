<?php

declare(strict_types=1);

namespace App\Domain\Channel\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Channel\Models\Channel;

final class UpdateChannelAction extends AbstractAction
{
    public function handle(Channel $channel, string $name): Channel
    {
        $channel->update([
            'name' => $name,
        ]);

        return $channel->refresh();
    }
}
