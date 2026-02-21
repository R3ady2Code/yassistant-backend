<?php

declare(strict_types=1);

namespace App\Http\Auth\Resources;

use App\Abstracts\AbstractJsonResource;
use Illuminate\Http\Request;

class UserResource extends AbstractJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
        ];
    }
}
