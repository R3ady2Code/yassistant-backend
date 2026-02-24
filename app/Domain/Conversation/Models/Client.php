<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Models;

use App\Domain\Channel\Models\Channel;
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
 * @property string $external_user_id
 * @property ?string $name
 * @property ?string $phone
 * @property ?Carbon $privacy_accepted_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Tenant $tenant
 * @property-read Channel $channel
 * @property-read Collection<int, Conversation> $conversations
 */
class Client extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'external_user_id',
        'name',
        'phone',
        'privacy_accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'privacy_accepted_at' => 'datetime',
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

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
