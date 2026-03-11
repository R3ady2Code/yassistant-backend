<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\DataObjects\OperationResult;
use App\Domain\AI\Enums\FallbackMessage;
use App\Domain\AI\Enums\RouterTool;
use App\Domain\AI\Exceptions\InvalidClassificationException;
use App\Domain\Conversation\Enums\ConversationMode;

final class RouterResponseHandler
{
    /**
     * @param  array{content: ?string, tool_calls: ?array<int, array<string, mixed>>, finish_reason: string}  $response
     *
     * @throws InvalidClassificationException
     */
    public function handle(array $response): OperationResult
    {
        if ($response['tool_calls'] === null) {
            throw new InvalidClassificationException('No tool call in response');
        }

        foreach ($response['tool_calls'] as $toolCall) {
            $toolName = RouterTool::tryFrom($toolCall['function']['name']);

            if ($toolName === null) {
                continue;
            }

            $args = json_decode($toolCall['function']['arguments'], true) ?? [];

            return match ($toolName) {
                RouterTool::SendResponse => $this->handleSendResponse($args),
                RouterTool::EscalateToHuman => $this->handleEscalation(),
                RouterTool::CreateBooking => $this->handleCreateBooking(),
                RouterTool::CancelBooking => $this->handleCancelBooking(),
                RouterTool::EditBooking => $this->handleEditBooking(),
            };
        }

        throw new InvalidClassificationException('No recognized tool call in response');
    }

    /**
     * @throws InvalidClassificationException
     */
    private function handleSendResponse(array $args): OperationResult
    {
        $text = $args['text'] ?? null;

        if ($text === null || $text === '') {
            throw new InvalidClassificationException('send_response called without text');
        }

        return new OperationResult(
            mode: ConversationMode::AI,
            responseText: $text,
        );
    }

    private function handleEscalation(): OperationResult
    {
        return new OperationResult(
            mode: ConversationMode::Escalated,
            responseText: FallbackMessage::Escalation->value,
        );
    }

    private function handleCreateBooking(): OperationResult
    {
        return new OperationResult(
            mode: ConversationMode::AI,
            responseText: '',
            routedOperation: RouterTool::CreateBooking,
        );
    }

    private function handleCancelBooking(): OperationResult
    {
        return new OperationResult(
            mode: ConversationMode::AI,
            responseText: FallbackMessage::CancelBookingUnavailable->value,
        );
    }

    private function handleEditBooking(): OperationResult
    {
        return new OperationResult(
            mode: ConversationMode::AI,
            responseText: FallbackMessage::EditBookingUnavailable->value,
        );
    }
}
