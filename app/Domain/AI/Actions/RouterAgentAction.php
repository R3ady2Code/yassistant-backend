<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Contracts\OpenAIContract;
use App\Domain\AI\DataObjects\OperationResult;
use App\Domain\AI\Enums\FallbackMessage;
use App\Domain\AI\Enums\RouterTool;
use App\Domain\AI\Exceptions\InvalidClassificationException;
use App\Domain\AI\Models\BotSettings;
use App\Domain\AI\Models\FaqEntry;
use App\Domain\AI\Services\BookingAgentService;
use App\Domain\AI\Services\RouterPromptBuilder;
use App\Domain\AI\Services\RouterResponseHandler;
use App\Domain\AI\Services\RouterToolRegistry;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Models\Client;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Services\BookingPipelineManager;
use App\Domain\Conversation\Services\ConversationContextLoader;

final class RouterAgentAction extends AbstractAction
{
    private const int MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly OpenAIContract $openAI,
        private readonly ConversationContextLoader $contextLoader,
        private readonly RouterPromptBuilder $promptBuilder,
        private readonly RouterToolRegistry $toolRegistry,
        private readonly RouterResponseHandler $responseHandler,
        private readonly BookingPipelineManager $pipelineManager,
        private readonly BookingAgentService $bookingAgent,
    ) {
        parent::__construct();
    }

    public function handle(BotSettings $settings, Client $client, Conversation $conversation): OperationResult
    {
        if ($this->pipelineManager->isActive($conversation)) {
            return $this->bookingAgent->handle($settings, $client, $conversation);
        }

        $faqEntries = FaqEntry::query()
            ->where('tenant_id', $settings->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $systemPrompt = $this->promptBuilder->build($settings, $client, $faqEntries);
        $tools = $this->toolRegistry->buildTools($settings);
        $contextMessages = $this->contextLoader->load($conversation);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$contextMessages,
        ];

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $response = $this->openAI->chatCompletion($settings->ai_model, $messages, $tools);

            try {
                $result = $this->responseHandler->handle($response);

                return $this->processResult($result, $settings, $client, $conversation);
            } catch (InvalidClassificationException) {
                continue;
            }
        }

        return new OperationResult(
            mode: ConversationMode::Escalated,
            responseText: FallbackMessage::Escalation->value,
        );
    }

    private function processResult(
        OperationResult $result,
        BotSettings $settings,
        Client $client,
        Conversation $conversation,
    ): OperationResult {
        if ($result->routedOperation === null) {
            return $result;
        }

        return match ($result->routedOperation) {
            RouterTool::CreateBooking => $this->bookingAgent->handle($settings, $client, $conversation),
            default => $result,
        };
    }
}
