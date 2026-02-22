<?php

declare(strict_types=1);

namespace App\Http\Conversations\Resources;

use App\Abstracts\AbstractJsonResource;
use App\Domain\Conversation\Models\Message;
use Illuminate\Http\Request;

/**
 * @mixin Message
 */
class MessageResource extends AbstractJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'type' => $this->type->value,
            'direction' => $this->direction->value,
            'sender_type' => $this->sender_type->value,
            'text' => $this->text,
            'attachments' => $this->attachments,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
