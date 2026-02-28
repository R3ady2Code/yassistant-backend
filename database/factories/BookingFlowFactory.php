<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Booking\Models\BookingFlow;
use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingFlow>
 */
class BookingFlowFactory extends Factory
{
    protected $model = BookingFlow::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(3, true),
            'ask_staff' => false,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
