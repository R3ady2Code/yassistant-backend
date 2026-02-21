<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Identity\Models\Tenant;
use App\Domain\Scenario\Enums\ScenarioStatus;
use App\Domain\Scenario\Enums\ScenarioType;
use App\Domain\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Scenario>
 */
class ScenarioFactory extends Factory
{
    protected $model = Scenario::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => ScenarioType::Custom,
            'schema' => ['nodes' => [], 'edges' => [], 'start_node' => null],
            'status' => ScenarioStatus::Active,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ScenarioType::Default,
        ]);
    }
}
