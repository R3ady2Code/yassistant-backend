<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Abstracts\AbstractController;
use App\Domain\Identity\Actions\GoogleLoginAction;
use App\Domain\Identity\Actions\LoginAction;
use App\Domain\Identity\Actions\RegisterTenantAction;
use App\Domain\Identity\DataObjects\GoogleLoginData;
use App\Domain\Identity\DataObjects\LoginData;
use App\Domain\Identity\DataObjects\RegisterTenantData;
use App\Http\Auth\Requests\GoogleLoginRequest;
use App\Http\Auth\Requests\LoginRequest;
use App\Http\Auth\Requests\RegisterRequest;
use App\Http\Auth\Resources\AuthResource;
use App\Http\Auth\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends AbstractController
{
    public function register(
        RegisterRequest $request,
        RegisterTenantAction $action,
    ): JsonResponse {
        $data = new RegisterTenantData(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            tenantName: $request->validated('tenant_name'),
        );

        $result = $action->handle($data);

        return response()->json(new AuthResource($result), 201);
    }

    public function login(
        LoginRequest $request,
        LoginAction $action,
    ): JsonResponse {
        $data = new LoginData(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        $result = $action->handle($data);

        return response()->json(new AuthResource($result));
    }

    public function google(
        GoogleLoginRequest $request,
        GoogleLoginAction $action,
    ): JsonResponse {
        $data = new GoogleLoginData(
            token: $request->validated('token'),
        );

        $result = $action->handle($data);

        return response()->json(new AuthResource($result));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
