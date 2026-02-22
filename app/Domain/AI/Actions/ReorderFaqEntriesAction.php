<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Models\FaqEntry;

final class ReorderFaqEntriesAction extends AbstractAction
{
    /**
     * @param  string[]  $orderedIds
     */
    public function handle(string $tenantId, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            FaqEntry::query()
                ->where('id', $id)
                ->where('tenant_id', $tenantId)
                ->update(['sort_order' => $index]);
        }
    }
}
