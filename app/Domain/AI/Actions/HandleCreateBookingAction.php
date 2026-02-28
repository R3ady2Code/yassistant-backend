<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\DataObjects\OperationResult;
use App\Domain\AI\Enums\FallbackMessage;
use App\Domain\AI\Models\BotSettings;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Models\Client;
use App\Domain\Conversation\Models\Conversation;

final class HandleCreateBookingAction extends AbstractAction
{
    public function handle(BotSettings $settings, Client $client, Conversation $conversation): OperationResult
    {
        // TODO
        return new OperationResult(
            mode: ConversationMode::AI,
            responseText: FallbackMessage::CreateBookingUnavailable->value,
        );
    }
}
