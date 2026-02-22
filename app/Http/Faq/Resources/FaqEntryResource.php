<?php

declare(strict_types=1);

namespace App\Http\Faq\Resources;

use App\Abstracts\AbstractJsonResource;
use App\Domain\AI\Models\FaqEntry;
use Illuminate\Http\Request;

/**
 * @mixin FaqEntry
 */
class FaqEntryResource extends AbstractJsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
