<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Enums\ConversationStatus;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'channel_id' => Channel::factory(),
            'external_chat_id' => (string) fake()->unique()->randomNumber(9),
            'mode' => ConversationMode::AI,
            'status' => ConversationStatus::Open,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ConversationStatus::Closed,
        ]);
    }
}
