<?php

declare(strict_types=1);

namespace App\Http\Auth\Controllers;

use App\Abstracts\AbstractController;
use App\Abstracts\Empty204Resource;
use App\Domain\Identity\Actions\GoogleLoginAction;
use App\Domain\Identity\Actions\LoginAction;
use App\Domain\Identity\Actions\RegisterTenantAction;
use App\Domain\Identity\DataObjects\LoginData;
use App\Domain\Identity\DataObjects\RegisterTenantData;
use App\Http\Auth\Requests\GoogleLoginRequest;
use App\Http\Auth\Requests\LoginRequest;
use App\Http\Auth\Requests\RegisterRequest;
use App\Http\Auth\Resources\AuthResource;
use App\Http\Auth\Resources\UserResource;
use Illuminate\Http\Request;

final class AuthController extends AbstractController
{
    public function register(
        RegisterRequest $request,
        RegisterTenantAction $registerTenantAction,
    ): AuthResource {
        $data = new RegisterTenantData(
            name: $request->name,
            email: $request->email,
            password: $request->password,
            tenantName: $request->tenant_name,
        );

        $result = $registerTenantAction->handle($data);

        return AuthResource::make($result);
    }

    public function login(
        LoginRequest $request,
        LoginAction $loginAction,
    ): AuthResource {
        $data = new LoginData(
            email: $request->email,
            password: $request->password,
        );

        $result = $loginAction->handle($data);

        return AuthResource::make($result);
    }

    public function google(
        GoogleLoginRequest $request,
        GoogleLoginAction $googleLoginAction,
    ): AuthResource {
        $result = $googleLoginAction->handle($request->token);

        return AuthResource::make($result);
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    public function logout(Request $request): Empty204Resource
    {
        $request->user()->currentAccessToken()->delete();

        return Empty204Resource::make(null);
    }
}
