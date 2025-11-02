<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Assert;

use InvalidArgumentException;

final class CommandLengthIsValid
{
    public static function assert(string $command): void
    {
        $length = strlen($command);

        if ($length < 1 || $length > 512) {
            throw new InvalidArgumentException(
                "SMTP command length must be between 1 and 512 characters, got {$length}"
            );
        }
    }
}
