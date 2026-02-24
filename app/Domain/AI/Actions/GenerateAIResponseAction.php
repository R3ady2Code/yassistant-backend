<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Contracts\OpenAIContract;
use App\Domain\AI\Enums\AIFunction;
use App\Domain\AI\Models\BotSettings;
use App\Domain\AI\Services\FunctionExecutor;
use App\Domain\AI\Services\FunctionRegistry;
use App\Domain\AI\Services\PromptBuilder;
use App\Domain\Channel\Contracts\TelegramContract;
use App\Domain\Conversation\DataObjects\HandleMessageData;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Enums\MessageDirection;
use App\Domain\Conversation\Enums\MessageType;
use App\Domain\Conversation\Enums\SenderType;
use App\Domain\Conversation\Events\ConversationEscalatedEvent;
use App\Domain\Conversation\Events\NewMessageEvent;
use App\Domain\Conversation\Models\Message;
use App\Domain\Conversation\Services\ConversationContextLoader;

final class GenerateAIResponseAction extends AbstractAction
{
    private const string FALLBACK_MESSAGE = 'Извините, не удалось обработать ваш запрос. Попробуйте позже.';

    private const string DEFAULT_ESCALATION_MESSAGE = 'Сейчас я переведу вас на оператора. Пожалуйста, подождите.';

    public function __construct(
        private readonly OpenAIContract $openAI,
        private readonly TelegramContract $telegram,
        private readonly PromptBuilder $promptBuilder,
        private readonly FunctionRegistry $functionRegistry,
        private readonly FunctionExecutor $functionExecutor,
        private readonly ConversationContextLoader $contextLoader,
    ) {
        parent::__construct();
    }

    public function handle(HandleMessageData $data): void
    {
        $conversation = $data->conversation;
        $settings = BotSettings::with('operations')
            ->where('tenant_id', $conversation->tenant_id)
            ->first();

        if ($settings === null) {
            $this->sendAndSave($data, self::FALLBACK_MESSAGE);

            return;
        }

        $systemPrompt = $this->promptBuilder->build($settings, $data->client, $conversation);
        $contextMessages = $this->contextLoader->load($conversation);
        $tools = $this->functionRegistry->forTenant($settings, $conversation);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$contextMessages,
        ];

        $maxIterations = $settings->max_function_calls ?? 5;

        for ($i = 0; $i < $maxIterations; $i++) {
            $response = $this->openAI->chatCompletion($settings->ai_model, $messages, $tools);

            if ($response['content'] !== null && $response['tool_calls'] === null) {
                $this->sendAndSave($data, $response['content']);

                return;
            }

            if ($response['tool_calls'] !== null) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $response['content'],
                    'tool_calls' => $response['tool_calls'],
                ];

                foreach ($response['tool_calls'] as $toolCall) {
                    $functionName = $toolCall['function']['name'];
                    $arguments = json_decode($toolCall['function']['arguments'], true) ?? [];
                    $aiFunction = AIFunction::tryFrom($functionName);

                    if ($aiFunction === null) {
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCall['id'],
                            'content' => json_encode(['error' => 'Unknown function'], JSON_UNESCAPED_UNICODE),
                        ];

                        continue;
                    }

                    $result = $this->functionExecutor->execute($aiFunction, $arguments, $conversation);

                    if ($result === FunctionExecutor::ESCALATE_MARKER) {
                        $this->escalate($data, $settings);

                        return;
                    }

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => $result,
                    ];
                }

                continue;
            }

            break;
        }

        $this->sendAndSave($data, self::FALLBACK_MESSAGE);
    }

    private function sendAndSave(HandleMessageData $data, string $text): void
    {
        $this->telegram->sendMessage(
            $data->botToken,
            $data->messageData->externalChatId,
            $text,
        );

        $message = Message::create([
            'conversation_id' => $data->conversation->id,
            'type' => MessageType::Text,
            'direction' => MessageDirection::Outgoing,
            'sender_type' => SenderType::Bot,
            'text' => $text,
        ]);

        $data->conversation->update(['last_message_at' => now()]);

        NewMessageEvent::dispatch($message, $data->conversation->tenant_id);
    }

    private function escalate(HandleMessageData $data, BotSettings $settings): void
    {
        $conversation = $data->conversation;
        $conversation->update(['mode' => ConversationMode::Escalated]);

        $escalationMessage = $settings->escalation_message ?? self::DEFAULT_ESCALATION_MESSAGE;

        $this->telegram->sendMessage(
            $data->botToken,
            $data->messageData->externalChatId,
            $escalationMessage,
        );

        Message::create([
            'conversation_id' => $conversation->id,
            'type' => MessageType::Text,
            'direction' => MessageDirection::Outgoing,
            'sender_type' => SenderType::Bot,
            'text' => $escalationMessage,
        ]);

        $conversation->update(['last_message_at' => now()]);

        ConversationEscalatedEvent::dispatch($conversation, $conversation->tenant_id);
    }
}
