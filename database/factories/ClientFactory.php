<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\Models\Client;
use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'channel_id' => Channel::factory(),
            'external_user_id' => (string) fake()->unique()->randomNumber(9),
            'name' => fake()->name(),
        ];
    }

    public function withPrivacyAccepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy_accepted_at' => now(),
        ]);
    }
}
