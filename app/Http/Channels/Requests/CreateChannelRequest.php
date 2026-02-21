<?php

declare(strict_types=1);

namespace App\Http\Channels\Requests;

use App\Domain\Channel\Enums\ChannelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string $type
 * @property-read string $name
 * @property-read string $bot_token
 */
class CreateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(ChannelType::class)],
            'name' => ['required', 'string', 'max:255'],
            'bot_token' => ['required', 'string'],
        ];
    }
}
