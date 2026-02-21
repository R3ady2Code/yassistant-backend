<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Channel\Enums\ChannelType;
use App\Domain\Channel\Models\Channel;
use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => fake()->randomElement(ChannelType::cases()),
            'name' => fake()->words(2, true),
            'is_active' => true,
        ];
    }

    public function telegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChannelType::Telegram,
        ]);
    }

    public function whatsapp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChannelType::WhatsApp,
        ]);
    }
}
