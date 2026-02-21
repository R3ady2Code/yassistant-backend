<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Models\BotSettings;
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
    private const string PRIVACY_PROMPT = 'Для продолжения работы с ботом необходимо принять политику конфиденциальности. Нажмите кнопку ниже для подтверждения.';

    private const string ACCEPT_CALLBACK_DATA = 'accept_privacy';

    private const string DEFAULT_GREETING = 'Здравствуйте! Чем могу помочь?';

    public function __construct(
        private readonly VaultContract $vault,
        private readonly TelegramContract $telegram,
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
            $this->handlePrivacyFlow($botToken, $channel, $client, $data);

            return;
        }

        $this->handleMessage($channel, $client, $data);
    }

    private function handlePrivacyFlow(string $botToken, Channel $channel, Client $client, IncomingMessageData $data): void
    {
        if ($data->type === MessageType::CallbackQuery && $data->text === self::ACCEPT_CALLBACK_DATA) {
            $client->update(['privacy_accepted_at' => now()]);

            $greeting = $this->resolveGreeting($channel->tenant_id);

            $this->telegram->sendMessage($botToken, $data->externalChatId, $greeting);

            $conversation = Conversation::create([
                'tenant_id' => $channel->tenant_id,
                'channel_id' => $channel->id,
                'client_id' => $client->id,
                'external_chat_id' => $data->externalChatId,
                'mode' => ConversationMode::AI,
                'last_message_at' => now(),
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'type' => MessageType::Text,
                'direction' => MessageDirection::Outgoing,
                'sender_type' => SenderType::Bot,
                'text' => $greeting,
            ]);

            return;
        }

        $this->telegram->sendMessage(
            $botToken,
            $data->externalChatId,
            self::PRIVACY_PROMPT,
            'HTML',
            [[['text' => '✅ Принимаю', 'callback_data' => self::ACCEPT_CALLBACK_DATA]]],
        );
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

    private function resolveGreeting(string $tenantId): string
    {
        $settings = BotSettings::where('tenant_id', $tenantId)->first();

        return $settings?->greeting_message ?? self::DEFAULT_GREETING;
    }
}
