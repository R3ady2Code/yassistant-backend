<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Models;

use App\Domain\Conversation\Enums\MessageDirection;
use App\Domain\Conversation\Enums\MessageType;
use App\Domain\Conversation\Enums\SenderType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $conversation_id
 * @property MessageType $type
 * @property MessageDirection $direction
 * @property SenderType $sender_type
 * @property ?string $text
 * @property ?array $attachments
 * @property ?array $metadata
 * @property Carbon $created_at
 * @property-read Conversation $conversation
 */
class Message extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'type',
        'direction',
        'sender_type',
        'text',
        'attachments',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'direction' => MessageDirection::class,
            'sender_type' => SenderType::class,
            'attachments' => 'json',
            'metadata' => 'json',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
