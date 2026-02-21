<?php

declare(strict_types=1);

namespace App\Domain\Channel\Models;

use App\Domain\Channel\Enums\ChannelType;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property ChannelType $type
 * @property string $name
 * @property string|null $external_id
 * @property string|null $bot_token_vault_path
 * @property string|null $webhook_url
 * @property string|null $webhook_secret
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Conversation> $conversations
 */
class Channel extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'external_id',
        'bot_token_vault_path',
        'webhook_url',
        'webhook_secret',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
