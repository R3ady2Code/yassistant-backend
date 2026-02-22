<?php

declare(strict_types=1);

namespace App\Http\Conversations\Resources;

use App\Abstracts\AbstractJsonResource;
use App\Domain\Conversation\Models\Conversation;
use Illuminate\Http\Request;

/**
 * @mixin Conversation
 */
class ConversationResource extends AbstractJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel_id' => $this->channel_id,
            'client_id' => $this->client_id,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'phone' => $this->client->phone,
            ]),
            'mode' => $this->mode->value,
            'status' => $this->status->value,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
