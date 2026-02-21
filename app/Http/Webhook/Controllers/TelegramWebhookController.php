<?php

declare(strict_types=1);

namespace App\Http\Webhook\Controllers;

use App\Abstracts\AbstractController;
use App\Abstracts\Empty204Resource;
use App\Adapters\Telegram\TelegramUpdateParser;
use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\Enums\MessageType;
use App\Domain\Identity\Contracts\VaultContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class TelegramWebhookController extends AbstractController
{
    public function __invoke(
        Request $request,
        Channel $channel,
        TelegramUpdateParser $parser,
        VaultContract $vault,
        TelegramContract $telegram,
    ): Empty204Resource {
        try {
            $data = $parser->parse($channel->id, $request->all());

            if ($data === null) {
                return Empty204Resource::make(null);
            }

            // TODO: replace with ProcessIncomingMessageAction
            if ($data->type === MessageType::Text && $data->text) {
                $botToken = $vault->get($channel->bot_token_vault_path);

                if ($botToken) {
                    $telegram->sendMessage($botToken, $data->externalChatId, "Echo: {$data->text}");
                }
            }

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
