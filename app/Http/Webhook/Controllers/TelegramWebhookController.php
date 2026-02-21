<?php

declare(strict_types=1);

namespace App\Http\Webhook\Controllers;

use App\Abstracts\AbstractController;
use App\Abstracts\Empty204Resource;
use App\Adapters\Telegram\TelegramUpdateParser;
use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Channel\Exceptions\BotTokenNotFoundException;
use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\Actions\ProcessIncomingMessageAction;
use App\Domain\Conversation\Actions\SendPrivacyMessageAction;
use App\Domain\Conversation\DataObjects\HandleMessageData;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Enums\ConversationStatus;
use App\Domain\Conversation\Models\Client;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Identity\Contracts\VaultContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SergiX44\Hydrator\Hydrator;

final class TelegramWebhookController extends AbstractController
{
    public function __construct(
        private readonly VaultContract $vault,
        private readonly TelegramContract $telegram,
    ) {
    }

    public function __invoke(
        Request $request,
        Channel $channel,
        TelegramUpdateParser $parser,
        ProcessIncomingMessageAction $processIncomingMessageAction,
        SendPrivacyMessageAction $sendPrivacyMessageAction
    ): Empty204Resource {
        try {
            $messageData = $parser->parse($channel->id, $request->all());
            $botToken = $this->vault->get($channel->bot_token_vault_path);

            if ($botToken === null) {
                throw new BotTokenNotFoundException($channel->id);
            }

            $client = Client::query()->firstOrCreate(
                [
                    'channel_id' => $channel->id,
                    'external_user_id' => $messageData->externalUserId,
                ],
                [
                    'tenant_id' => $channel->tenant_id,
                    'name' => $messageData->senderName,
                ],
            );

            $conversation = Conversation::query()->firstOrCreate(
                [
                    'channel_id' => $channel->id,
                    'client_id' => $client->id,
                    'external_chat_id' => $messageData->externalChatId,
                    'status' => ConversationStatus::Open,
                ],
                [
                    'tenant_id' => $channel->tenant_id,
                    'mode' => ConversationMode::AI,
                ],
            );

            $handleMessageData = new HandleMessageData(
                botToken: $botToken,
                conversation: $conversation,
                client: $client,
                messageData: $messageData
            );

            if (!$client->hasAcceptedPrivacy()) {
                $sendPrivacyMessageAction->handle($handleMessageData);

                return Empty204Resource::make(null);
            }

            if ($conversation->mode !== ConversationMode::AI) {
                return Empty204Resource::make(null);
            }



            $processIncomingMessageAction->handle($channel, $messageData);

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
