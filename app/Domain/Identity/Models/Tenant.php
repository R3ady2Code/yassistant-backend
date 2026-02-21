<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

use App\Domain\AI\Models\BotSettings;
use App\Domain\Booking\Models\TenantBranch;
use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $yclients_vault_path
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Channel> $channels
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Conversation> $conversations
 * @property-read BotSettings|null $botSettings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Scenario> $scenarios
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TenantBranch> $branches
 */
class Tenant extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'yclients_vault_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function botSettings(): HasOne
    {
        return $this->hasOne(BotSettings::class);
    }

    public function scenarios(): HasMany
    {
        return $this->hasMany(Scenario::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(TenantBranch::class);
    }
}
