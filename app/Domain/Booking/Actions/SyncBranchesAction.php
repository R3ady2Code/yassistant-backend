<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Booking\Contracts\YClientsContract;
use App\Domain\Booking\Enums\TenantBranchStatus;
use App\Domain\Booking\Models\TenantBranch;
use App\Domain\Identity\Contracts\VaultContract;
use App\Domain\Identity\Models\Tenant;
use Illuminate\Support\Collection;
use RuntimeException;

final class SyncBranchesAction extends AbstractAction
{
    public function __construct(
        private readonly YClientsContract $yclients,
        private readonly VaultContract $vault,
    ) {
        parent::__construct();
    }

    /**
     * @return Collection<int, TenantBranch>
     */
    public function handle(Tenant $tenant): Collection
    {
        $apiToken = $this->vault->get($tenant->yclients_vault_path);

        if (! $apiToken) {
            throw new RuntimeException("YClients API token not found for tenant [{$tenant->id}]");
        }

        $remoteBranches = $this->yclients->getBranches($apiToken);
        $existingIds = $tenant->branches()->pluck('yclients_branch_id')->all();
        $remoteIds = array_column($remoteBranches, 'id');

        foreach ($remoteBranches as $branch) {
            TenantBranch::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'yclients_branch_id' => $branch['id'],
                ],
                [
                    'name' => $branch['title'] ?? $branch['name'] ?? '',
                    'address' => $branch['address'] ?? null,
                    'phone' => $branch['phone'] ?? null,
                    'status' => TenantBranchStatus::Active,
                ],
            );
        }

        $removedIds = array_diff($existingIds, $remoteIds);

        if ($removedIds) {
            $tenant->branches()
                ->whereIn('yclients_branch_id', $removedIds)
                ->update(['status' => TenantBranchStatus::Inactive]);
        }

        return $tenant->branches()->orderBy('name')->get();
    }
}
