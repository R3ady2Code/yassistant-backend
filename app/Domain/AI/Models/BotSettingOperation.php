<?php

declare(strict_types=1);

namespace App\Domain\AI\Models;

use App\Domain\AI\Enums\BotOperation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $bot_setting_id
 * @property BotOperation $operation
 * @property bool $is_enabled
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read BotSettings $botSetting
 */
class BotSettingOperation extends Model
{
    protected $fillable = [
        'bot_setting_id',
        'operation',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'operation' => BotOperation::class,
            'is_enabled' => 'boolean',
        ];
    }

    public function botSetting(): BelongsTo
    {
        return $this->belongsTo(BotSettings::class);
    }
}
