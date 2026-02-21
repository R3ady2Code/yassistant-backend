<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Models;

use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Conversation\Enums\ConversationStatus;
use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $channel_id
 * @property ?string $client_id
 * @property string $external_chat_id
 * @property ConversationMode $mode
 * @property ?array $scenario_state
 * @property ConversationStatus $status
 * @property ?Carbon $last_message_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Tenant $tenant
 * @property-read Channel $channel
 * @property-read ?Client $client
 * @property-read Collection<int, Message> $messages
 */
class Conversation extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'client_id',
        'external_chat_id',
        'mode',
        'scenario_state',
        'status',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'mode' => ConversationMode::class,
            'scenario_state' => 'json',
            'status' => ConversationStatus::class,
            'last_message_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
