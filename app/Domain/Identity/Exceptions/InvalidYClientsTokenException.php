<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

final class InvalidYClientsTokenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('YClients API token is invalid or expired');
    }
}
