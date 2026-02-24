<?php

declare(strict_types=1);

namespace App\Http\BookingFlows\Resources;

use App\Abstracts\AbstractJsonResource;
use Illuminate\Http\Request;

final class FlowStepResource extends AbstractJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_text' => $this->question_text,
            'answer_type' => $this->answer_type->value,
            'is_required' => $this->is_required,
            'config' => $this->config,
            'sort_order' => $this->sort_order,
        ];
    }
}
