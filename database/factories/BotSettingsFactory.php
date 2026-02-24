<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\AI\Models\BotSettings;
use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BotSettings>
 */
class BotSettingsFactory extends Factory
{
    protected $model = BotSettings::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'system_prompt' => fake()->paragraph(),
            'ai_model' => 'gpt-4o',
            'max_function_calls' => 5,
            'greeting_message' => fake()->sentence(),
            'escalation_message' => fake()->sentence(),
        ];
    }
}
