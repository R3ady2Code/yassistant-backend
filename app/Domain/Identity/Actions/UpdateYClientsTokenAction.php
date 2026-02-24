<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Booking\Contracts\YClientsContract;
use App\Domain\Identity\Contracts\VaultContract;
use App\Domain\Identity\DataObjects\UpdateYClientsTokenData;
use App\Domain\Identity\Exceptions\InvalidYClientsTokenException;
use App\Domain\Identity\Models\Tenant;
use Throwable;

final class UpdateYClientsTokenAction extends AbstractAction
{
    public function __construct(
        private readonly YClientsContract $yclients,
        private readonly VaultContract $vault,
    ) {
        parent::__construct();
    }

    public function handle(UpdateYClientsTokenData $data): Tenant
    {
        $tenant = Tenant::findOrFail($data->tenantId);

        try {
            $this->yclients->getBranches($data->apiToken);
        } catch (Throwable) {
            throw new InvalidYClientsTokenException();
        }

        $path = $tenant->yclients_vault_path ?? "tenants/{$tenant->id}/yclients_api_token";

        $this->vault->put($path, $data->apiToken);

        $tenant->update(['yclients_vault_path' => $path]);

        return $tenant->refresh();
    }
}
