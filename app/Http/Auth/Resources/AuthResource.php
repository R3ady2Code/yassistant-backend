<?php

declare(strict_types=1);

namespace App\Http\Auth\Resources;

use App\Abstracts\AbstractJsonResource;
use App\Domain\Identity\DataObjects\AuthResultData;
use Illuminate\Http\Request;

class AuthResource extends AbstractJsonResource
{
    public function __construct(
        private readonly AuthResultData $authResult,
    ) {
        parent::__construct($authResult->user);
    }

    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->authResult->user),
            'token' => $this->authResult->token,
        ];
    }
}
