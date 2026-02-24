<?php

declare(strict_types=1);

namespace App\Domain\Booking\Enums;

enum AnswerType: string
{
    case Number = 'number';
    case Choice = 'choice';
    case Text = 'text';
    case YesNo = 'yes_no';
}
