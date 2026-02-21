<?php

declare(strict_types=1);

namespace App\Abstracts;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class AbstractJsonResource extends JsonResource
{
    public static $wrap = 'data';
}
