<?php

declare(strict_types=1);

namespace App\Domain\Scenario\Enums;

enum ScenarioType: string
{
    case Default = 'default';
    case Custom = 'custom';
}
