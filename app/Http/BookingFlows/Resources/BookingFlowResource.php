<?php

declare(strict_types=1);

namespace App\Http\BookingFlows\Resources;

use App\Abstracts\AbstractJsonResource;
use Illuminate\Http\Request;

final class BookingFlowResource extends AbstractJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ask_staff' => $this->ask_staff,
            'is_active' => $this->is_active,
            'steps' => FlowStepResource::collection($this->whenLoaded('steps')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
