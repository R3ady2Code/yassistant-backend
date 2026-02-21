<?php

declare(strict_types=1);

namespace App\Http\Webhook\Controllers;

use App\Abstracts\AbstractController;
use App\Abstracts\Empty204Resource;
use App\Adapters\Telegram\TelegramUpdateParser;
use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\Actions\ProcessIncomingMessageAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class TelegramWebhookController extends AbstractController
{
    public function __invoke(
        Request $request,
        Channel $channel,
        TelegramUpdateParser $parser,
        ProcessIncomingMessageAction $action,
    ): Empty204Resource {
        try {
            $data = $parser->parse($channel->id, $request->all());

            if ($data === null) {
                return Empty204Resource::make(null);
            }

            $action->handle($channel, $data);

            return Empty204Resource::make(null);
        } catch (\Throwable $e) {
            Log::error('Telegram webhook processing failed', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);

            return Empty204Resource::make(null);
        }
    }
}
