<?php

declare(strict_types=1);

namespace App\Domain\AI\Enums;

enum RouterTool: string
{
    case SendResponse = 'send_response';
    case EscalateToHuman = 'escalate_to_human';
    case CreateBooking = 'create_booking';
    case CancelBooking = 'cancel_booking';
    case EditBooking = 'edit_booking';

    public static function fromBotOperation(BotOperation $operation): self
    {
        return match ($operation) {
            BotOperation::CreateBooking => self::CreateBooking,
            BotOperation::CancelBooking => self::CancelBooking,
            BotOperation::EditBooking => self::EditBooking,
            BotOperation::AskFaq => self::SendResponse,
        };
    }
}
