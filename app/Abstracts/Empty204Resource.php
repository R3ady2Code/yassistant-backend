<?php

declare(strict_types=1);

namespace App\Abstracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class Empty204Resource extends JsonResource
{
    public function toResponse($request): JsonResponse
    {
        return response()->json(status: ResponseAlias::HTTP_NO_CONTENT);
    }
}
