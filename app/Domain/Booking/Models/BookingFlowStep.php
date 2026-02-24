<?php

declare(strict_types=1);

namespace App\Domain\Booking\Models;

use App\Domain\Booking\Enums\AnswerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $flow_id
 * @property string $question_text
 * @property AnswerType $answer_type
 * @property bool $is_required
 * @property array $config
 * @property int $sort_order
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read BookingFlow $flow
 */
class BookingFlowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_id',
        'question_text',
        'answer_type',
        'is_required',
        'config',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'answer_type' => AnswerType::class,
            'is_required' => 'boolean',
            'config' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(BookingFlow::class, 'flow_id');
    }

    public function describeExpectedAnswer(): string
    {
        return match ($this->answer_type) {
            AnswerType::Number => sprintf(
                'целое число от %d до %d',
                $this->config['min'] ?? 1,
                $this->config['max'] ?? 100,
            ),
            AnswerType::Choice => sprintf(
                'один из вариантов: %s',
                implode(', ', $this->config['options'] ?? []),
            ),
            AnswerType::Text => 'текстовый ответ',
            AnswerType::YesNo => 'да или нет',
        };
    }
}
