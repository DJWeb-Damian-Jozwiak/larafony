<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Exceptions;

use Exception;

class CommandNotFoundError extends Exception
{
    public function __construct(string $command)
    {
        parent::__construct("Command {$command} not found");
    }
}
