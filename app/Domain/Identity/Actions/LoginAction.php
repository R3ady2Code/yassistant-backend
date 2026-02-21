<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Identity\DataObjects\AuthResultData;
use App\Domain\Identity\DataObjects\LoginData;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoginAction extends AbstractAction
{
    public function handle(LoginData $data): AuthResultData
    {
        $user = User::where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return new AuthResultData(user: $user, token: $token);
    }
}
