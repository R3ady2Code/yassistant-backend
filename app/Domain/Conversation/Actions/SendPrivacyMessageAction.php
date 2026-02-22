<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Models\BotSettings;
use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Conversation\DataObjects\HandleMessageData;
use App\Domain\Conversation\Enums\MessageDirection;
use App\Domain\Conversation\Enums\MessageType;
use App\Domain\Conversation\Enums\SenderType;
use App\Domain\Conversation\Models\Message;
use Illuminate\Support\Carbon;

final class SendPrivacyMessageAction extends AbstractAction
{
    private const string PRIVACY_PROMPT = 'Для продолжения работы с ботом необходимо принять политику конфиденциальности. Нажмите кнопку ниже для подтверждения.';

    private const string ACCEPT_CALLBACK_DATA = 'accept_privacy';

    private const string DEFAULT_GREETING = 'Здравствуйте! Чем могу помочь?';

    public function __construct(
        private readonly TelegramContract $telegram,
    ) {
        parent::__construct();
    }

    public function handle(HandleMessageData $data): void
    {
        if ($data->messageData->text !== self::ACCEPT_CALLBACK_DATA) {
            $this->telegram->sendMessage(
                $data->botToken,
                $data->messageData->externalChatId,
                self::PRIVACY_PROMPT,
                'HTML',
                [[['text' => '✅ Принимаю', 'callback_data' => self::ACCEPT_CALLBACK_DATA]]],
            );

            return;
        }

        $data->client->privacy_accepted_at = Carbon::now();
        $data->client->save();

        $settings = BotSettings::where('tenant_id', $data->conversation->tenant_id)->first();
        $greeting = $settings?->greeting_message ?? self::DEFAULT_GREETING;

        $this->telegram->sendMessage($data->botToken, $data->messageData->externalChatId, $greeting);

        Message::create([
            'conversation_id' => $data->conversation->id,
            'type' => MessageType::Text,
            'direction' => MessageDirection::Outgoing,
            'sender_type' => SenderType::Bot,
            'text' => $greeting,
        ]);
    }
}
