<?php

declare(strict_types=1);

namespace App\Http\YClients\Controllers;

use App\Abstracts\AbstractController;
use App\Domain\Identity\Actions\UpdateYClientsTokenAction;
use App\Domain\Identity\Contracts\VaultContract;
use App\Domain\Identity\DataObjects\UpdateYClientsTokenData;
use App\Domain\Identity\Exceptions\InvalidYClientsTokenException;
use App\Domain\Identity\Models\Tenant;
use App\Http\YClients\Requests\UpdateYClientsTokenRequest;
use App\Http\YClients\Resources\YClientsSettingsResource;
use Illuminate\Http\JsonResponse;

final class YClientsSettingsController extends AbstractController
{
    public function show(VaultContract $vault): JsonResponse
    {
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);

        $isConnected = $tenant->yclients_vault_path !== null
            && $vault->has($tenant->yclients_vault_path);

        return response()->json(new YClientsSettingsResource([
            'is_connected' => $isConnected,
        ]));
    }

    public function updateToken(
        UpdateYClientsTokenRequest $request,
        UpdateYClientsTokenAction $action,
    ): JsonResponse {
        try {
            $action->handle(new UpdateYClientsTokenData(
                tenantId: auth()->user()->tenant_id,
                apiToken: $request->validated('api_token'),
            ));
        } catch (InvalidYClientsTokenException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'YClients token updated successfully']);
    }

    public function disconnect(VaultContract $vault): JsonResponse
    {
        $tenant = Tenant::findOrFail(auth()->user()->tenant_id);

        if ($tenant->yclients_vault_path !== null) {
            $vault->delete($tenant->yclients_vault_path);
            $tenant->update(['yclients_vault_path' => null]);
        }

        return response()->json(null, 204);
    }
}
