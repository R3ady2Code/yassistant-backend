<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\DataObjects\IncomingMessageData;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Enums\ConversationStatus;
use App\Domain\Conversation\Enums\MessageDirection;
use App\Domain\Conversation\Enums\MessageType;
use App\Domain\Conversation\Enums\SenderType;
use App\Domain\Conversation\Models\Client;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Models\Message;
use App\Domain\Identity\Contracts\VaultContract;

final class ProcessIncomingMessageAction extends AbstractAction
{
    public function __construct(
        private readonly VaultContract $vault,
        private readonly TelegramContract $telegram,
        private readonly SendPrivacyMessageAction $sendPrivacyMessage,
    ) {
        parent::__construct();
    }

    public function handle(Channel $channel, IncomingMessageData $data): void
    {
        $botToken = $this->vault->get($channel->bot_token_vault_path);

        if ($botToken === null) {
            return;
        }

        $client = Client::firstOrCreate(
            [
                'channel_id' => $channel->id,
                'external_user_id' => $data->externalUserId,
            ],
            [
                'tenant_id' => $channel->tenant_id,
                'name' => $data->senderName,
            ],
        );

        if ($data->callbackQueryId !== null) {
            $this->telegram->answerCallbackQuery($botToken, $data->callbackQueryId);
        }

        if (! $client->hasAcceptedPrivacy()) {
            $this->sendPrivacyMessage->handle($botToken, $channel, $client, $data);

            return;
        }

        $this->handleMessage($channel, $client, $data);
    }

    private function handleMessage(Channel $channel, Client $client, IncomingMessageData $data): void
    {
        $conversation = Conversation::firstOrCreate(
            [
                'channel_id' => $channel->id,
                'external_chat_id' => $data->externalChatId,
                'status' => ConversationStatus::Open,
            ],
            [
                'tenant_id' => $channel->tenant_id,
                'client_id' => $client->id,
                'mode' => ConversationMode::AI,
            ],
        );

        $conversation->update(['last_message_at' => now()]);

        Message::create([
            'conversation_id' => $conversation->id,
            'type' => $data->type,
            'direction' => MessageDirection::Incoming,
            'sender_type' => SenderType::Client,
            'text' => $data->text,
            'attachments' => $data->attachments,
        ]);

        // TODO: route to AI/scenario
    }
}
