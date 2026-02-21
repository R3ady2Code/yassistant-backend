<?php

declare(strict_types=1);

namespace App\Domain\Scenario\Models;

use App\Domain\Identity\Models\Tenant;
use App\Domain\Scenario\Enums\ScenarioType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $slug
 * @property ScenarioType $type
 * @property array $schema
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Tenant $tenant
 */
class Scenario extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
        'schema',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ScenarioType::class,
            'schema' => 'json',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isDefault(): bool
    {
        return $this->type === ScenarioType::Default;
    }
}
