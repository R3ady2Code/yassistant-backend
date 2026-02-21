<?php

declare(strict_types=1);

namespace App\Domain\AI\Models;

use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $system_prompt
 * @property string $ai_model
 * @property array $allowed_operations
 * @property int $max_function_calls
 * @property string|null $greeting_message
 * @property string|null $escalation_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Tenant $tenant
 */
class BotSettings extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'system_prompt',
        'ai_model',
        'allowed_operations',
        'max_function_calls',
        'greeting_message',
        'escalation_message',
    ];

    protected function casts(): array
    {
        return [
            'allowed_operations' => 'json',
            'max_function_calls' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
