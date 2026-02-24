<?php

declare(strict_types=1);

namespace App\Domain\AI\Models;

use App\Domain\AI\Enums\BotOperation;
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
 * @property ?string $system_prompt
 * @property string $ai_model
 * @property int $max_function_calls
 * @property ?string $greeting_message
 * @property ?string $escalation_message
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Tenant $tenant
 * @property-read Collection<int, BotSettingOperation> $operations
 */
class BotSettings extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'system_prompt',
        'ai_model',
        'max_function_calls',
        'greeting_message',
        'escalation_message',
    ];

    protected function casts(): array
    {
        return [
            'max_function_calls' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(BotSettingOperation::class, 'bot_setting_id');
    }

    public function seedDefaultOperations(): void
    {
        foreach (BotOperation::cases() as $operation) {
            $this->operations()->firstOrCreate(
                ['operation' => $operation],
                ['is_enabled' => false],
            );
        }
    }

    /**
     * @return string[]
     */
    public function allowedOperations(): array
    {
        return $this->operations
            ->where('is_enabled', true)
            ->pluck('operation')
            ->map(fn ($op) => $op instanceof BotOperation ? $op->value : $op)
            ->all();
    }

    public function isOperationEnabled(BotOperation $operation): bool
    {
        return $this->operations
            ->where('operation', $operation)
            ->where('is_enabled', true)
            ->isNotEmpty();
    }
}
