<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Booking\Enums\AnswerType;
use App\Domain\Booking\Models\BookingFlow;
use App\Domain\Booking\Models\BookingFlowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingFlowStep>
 */
class BookingFlowStepFactory extends Factory
{
    protected $model = BookingFlowStep::class;

    public function definition(): array
    {
        return [
            'flow_id' => BookingFlow::factory(),
            'question_text' => fake()->sentence() . '?',
            'answer_type' => AnswerType::Text,
            'is_required' => true,
            'config' => [],
            'sort_order' => 0,
        ];
    }
}
