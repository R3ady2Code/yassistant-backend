<?php

declare(strict_types=1);

namespace App\Http\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string $token
 */
class GoogleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
        ];
    }
}
