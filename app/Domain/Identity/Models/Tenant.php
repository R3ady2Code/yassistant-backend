<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

use App\Domain\AI\Models\BotSettings;
use App\Domain\Booking\Models\TenantBranch;
use App\Domain\Channel\Models\Channel;
use App\Domain\Conversation\Models\Conversation;
use App\Domain\Identity\Enums\TenantStatus;
use App\Domain\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property ?string $yclients_vault_path
 * @property TenantStatus $status
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Channel> $channels
 * @property-read Collection<int, Conversation> $conversations
 * @property-read ?BotSettings $botSettings
 * @property-read Collection<int, Scenario> $scenarios
 * @property-read Collection<int, TenantBranch> $branches
 */
class Tenant extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'yclients_vault_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
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
