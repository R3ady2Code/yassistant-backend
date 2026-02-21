<?php

declare(strict_types=1);

namespace App\Http\Channels\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string $name
 */
class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
