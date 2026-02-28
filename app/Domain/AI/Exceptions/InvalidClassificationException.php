<?php

declare(strict_types=1);

namespace App\Domain\AI\Exceptions;

use RuntimeException;

final class InvalidClassificationException extends RuntimeException
{
    public function __construct(string $reason)
    {
        parent::__construct("Invalid classification response: {$reason}");
    }
}
