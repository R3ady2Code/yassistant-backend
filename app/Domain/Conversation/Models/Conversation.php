<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Models;

use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\Enums\ConversationMode;
use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $channel_id
 * @property string $external_chat_id
 * @property string|null $client_name
 * @property string|null $client_phone
 * @property ConversationMode $mode
 * @property array|null $scenario_state
 * @property bool $is_closed
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Tenant $tenant
 * @property-read Channel $channel
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Message> $messages
 */
class Conversation extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'external_chat_id',
        'client_name',
        'client_phone',
        'mode',
        'scenario_state',
        'is_closed',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'mode' => ConversationMode::class,
            'scenario_state' => 'json',
            'is_closed' => 'boolean',
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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
