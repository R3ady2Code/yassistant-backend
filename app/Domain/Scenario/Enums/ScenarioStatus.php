<?php

declare(strict_types=1);

namespace App\Domain\Scenario\Enums;

enum ScenarioStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
