<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Booking\Models\TenantBranch;
use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantBranch>
 */
class TenantBranchFactory extends Factory
{
    protected $model = TenantBranch::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'yclients_branch_id' => fake()->unique()->numberBetween(100000, 999999),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
