<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Conversation\Enums\MessageDirection;
use App\Domain\Conversation\Enums\MessageType;
use App\Domain\Conversation\Enums\SenderType;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Conversation\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'type' => MessageType::Text,
            'direction' => MessageDirection::Incoming,
            'sender_type' => SenderType::Client,
            'text' => fake()->sentence(),
        ];
    }

    public function outgoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => MessageDirection::Outgoing,
            'sender_type' => SenderType::Bot,
        ]);
    }
}
