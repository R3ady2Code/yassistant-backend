<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\DataObjects\CreateFaqEntryData;
use App\Domain\AI\Models\FaqEntry;

final class CreateFaqEntryAction extends AbstractAction
{
    public function handle(CreateFaqEntryData $data): FaqEntry
    {
        $maxOrder = FaqEntry::query()
            ->where('tenant_id', $data->tenantId)
            ->max('sort_order') ?? -1;

        return FaqEntry::create([
            'tenant_id' => $data->tenantId,
            'question' => $data->question,
            'answer' => $data->answer,
            'sort_order' => $maxOrder + 1,
        ]);
    }
}
