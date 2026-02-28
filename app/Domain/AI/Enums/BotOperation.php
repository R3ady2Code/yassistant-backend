<?php

declare(strict_types=1);

namespace App\Domain\AI\Enums;

enum BotOperation: string
{
    case CreateBooking = 'create_booking';
    case CancelBooking = 'cancel_booking';
    case EditBooking = 'edit_booking';
    case AskFaq = 'ask_faq';
}
