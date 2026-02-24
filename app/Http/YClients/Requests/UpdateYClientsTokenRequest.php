<?php

declare(strict_types=1);

namespace App\Http\YClients\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateYClientsTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'api_token' => ['required', 'string'],
        ];
    }
}
