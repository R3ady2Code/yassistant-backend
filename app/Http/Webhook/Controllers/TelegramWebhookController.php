<?php

declare(strict_types=1);

namespace App\Http\Webhook\Controllers;

use App\Abstracts\AbstractController;
use App\Abstracts\Empty204Resource;
use App\Adapters\Telegram\TelegramUpdateParser;
use App\Domain\AI\Actions\GetBotOperationAction;
use App\Domain\AI\DataObjects\OperationResult;
use App\Domain\AI\Enums\BotOperation;
use App\Domain\AI\Models\BotSettings;
use App\Domain\Channel\Exceptions\BotTokenNotFoundException;
use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\Actions\EscalateConversationAction;
use App\Domain\Conversation\Actions\ProcessIncomingMessageAction;
use App\Domain\Conversation\Actions\SendPrivacyMessageAction;
use App\Domain\Conversation\Actions\SendTelegramMessageAction;
use App\Domain\Conversation\DataObjects\HandleMessageData;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Enums\ConversationStatus;
use App\Domain\Conversation\Models\Client;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Identity\Contracts\VaultContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class TelegramWebhookController extends AbstractController
{
    private const string FALLBACK_MESSAGE = 'Извините, не удалось обработать ваш запрос. Попробуйте позже.';

    public function __invoke(
        Request $request,
        Channel $channel,
        VaultContract $vault,
        TelegramUpdateParser $parser,
        ProcessIncomingMessageAction $processIncomingMessage,
        SendPrivacyMessageAction $sendPrivacyMessage,
        GetBotOperationAction $getBotOperation,
        EscalateConversationAction $escalateConversation,
        SendTelegramMessageAction $sendTelegramMessage,
    ): Empty204Resource {
        try {
            $messageData = $parser->parse($channel->id, $request->all());
            $botToken = $vault->get($channel->bot_token_vault_path);

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
                messageData: $messageData,
            );

            if ($client->privacy_accepted_at === null) {
                $sendPrivacyMessage->handle($handleMessageData);

                return Empty204Resource::make(null);
            }

            $processIncomingMessage->handle($handleMessageData);

            if ($conversation->mode !== ConversationMode::AI) {
                return Empty204Resource::make(null);
            }

            $settings = BotSettings::with('operations')
                ->where('tenant_id', $conversation->tenant_id)
                ->first();

            if ($settings === null) {
                $sendTelegramMessage->handle($botToken, $conversation, self::FALLBACK_MESSAGE);

                return Empty204Resource::make(null);
            }

            // Step 1: Determine BotOperation
            $operation = $getBotOperation->handle($settings, $conversation);

            // Step 2: Execute operation → OperationResult
            $result = match ($operation) {
                BotOperation::CreateBooking => new OperationResult(), // TODO
                BotOperation::CancelBooking => new OperationResult(), // TODO
                BotOperation::EditBooking => new OperationResult(), // TODO
                BotOperation::FAQ => new OperationResult(), // TODO
                null => new OperationResult(), // TODO: general AI response
            };

            // Step 3: Handle mode change + send response
            if ($result->modeChange === ConversationMode::Escalated) {
                $escalateConversation->handle($conversation, $botToken);

                return Empty204Resource::make(null);
            }

            if ($result->responseText !== null) {
                $sendTelegramMessage->handle($botToken, $conversation, $result->responseText);
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
