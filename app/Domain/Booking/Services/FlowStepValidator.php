<?php

declare(strict_types=1);

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Enums\AnswerType;
use App\Domain\Booking\Models\BookingFlowStep;

final class FlowStepValidator
{
    public function validate(BookingFlowStep $step, string $extracted): int|string|bool|null
    {
        return match ($step->answer_type) {
            AnswerType::Number => $this->validateNumber($step, $extracted),
            AnswerType::Choice => $this->validateChoice($step, $extracted),
            AnswerType::Text => $this->validateText($step, $extracted),
            AnswerType::YesNo => $this->validateYesNo($extracted),
        };
    }

    private function validateNumber(BookingFlowStep $step, string $raw): ?int
    {
        if (! is_numeric($raw)) {
            return null;
        }

        $value = (int) $raw;
        $min = $step->config['min'] ?? 1;
        $max = $step->config['max'] ?? 100;

        return ($value >= $min && $value <= $max) ? $value : null;
    }

    private function validateChoice(BookingFlowStep $step, string $raw): ?string
    {
        $options = $step->config['options'] ?? [];

        return in_array($raw, $options, true) ? $raw : null;
    }

    private function validateText(BookingFlowStep $step, string $raw): ?string
    {
        $maxLength = $step->config['max_length'] ?? 500;

        return mb_strlen($raw) <= $maxLength ? $raw : null;
    }

    private function validateYesNo(string $raw): ?bool
    {
        return match ($raw) {
            'yes', 'true', '1' => true,
            'no', 'false', '0' => false,
            default => null,
        };
    }
}
