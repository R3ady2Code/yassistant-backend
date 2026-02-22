<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Models\FaqEntry;

final class DeleteFaqEntryAction extends AbstractAction
{
    public function handle(FaqEntry $faqEntry): void
    {
        $faqEntry->delete();
    }
}
