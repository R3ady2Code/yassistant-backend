<?php

declare(strict_types=1);

namespace App\Domain\AI\Enums;

enum FallbackMessage: string
{
    case Escalation = 'Извините, не совсем понял вас. Сейчас подключу менеджера, он вам поможет.';
    case CreateBookingUnavailable = 'Функция записи временно недоступна.';
    case CancelBookingUnavailable = 'Функция отмены записи временно недоступна.';
    case EditBookingUnavailable = 'Функция изменения записи временно недоступна.';
}
