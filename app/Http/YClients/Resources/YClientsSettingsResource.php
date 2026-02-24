<?php

declare(strict_types=1);

namespace App\Http\YClients\Resources;

use App\Abstracts\AbstractJsonResource;
use Illuminate\Http\Request;

final class YClientsSettingsResource extends AbstractJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'is_connected' => $this->resource['is_connected'],
        ];
    }
}
