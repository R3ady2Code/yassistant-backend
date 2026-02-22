<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\DataObjects\UpdateFaqEntryData;
use App\Domain\AI\Models\FaqEntry;

final class UpdateFaqEntryAction extends AbstractAction
{
    public function handle(FaqEntry $faqEntry, UpdateFaqEntryData $data): FaqEntry
    {
        $fields = [];

        if ($data->question !== null) {
            $fields['question'] = $data->question;
        }

        if ($data->answer !== null) {
            $fields['answer'] = $data->answer;
        }

        if ($data->isActive !== null) {
            $fields['is_active'] = $data->isActive;
        }

        if ($fields !== []) {
            $faqEntry->update($fields);
        }

        return $faqEntry->refresh();
    }
}
