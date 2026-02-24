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
            'yclients_service_id' => fake()->unique()->numberBetween(1, 99999),
            'yclients_service_name' => fake()->words(2, true),
            'yclients_branch_id' => fake()->numberBetween(1, 99999),
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
