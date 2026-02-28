<?php

declare(strict_types=1);

namespace App\Domain\Booking\Models;

use App\Domain\Identity\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $tenant_id
 * @property string $name
 * @property bool $ask_staff
 * @property bool $is_active
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Tenant $tenant
 * @property-read Collection<int, BookingFlowStep> $steps
 */
class BookingFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'ask_staff',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ask_staff' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(BookingFlowStep::class, 'flow_id')
            ->orderBy('sort_order');
    }
}
