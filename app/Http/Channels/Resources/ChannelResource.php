<?php

declare(strict_types=1);

namespace App\Http\Channels\Resources;

use App\Abstracts\AbstractJsonResource;
use Illuminate\Http\Request;

class ChannelResource extends AbstractJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'type' => $this->type->value,
            'name' => $this->name,
            'external_id' => $this->external_id,
            'webhook_url' => $this->webhook_url,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
