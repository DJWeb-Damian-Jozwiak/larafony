<?php

declare(strict_types=1);

namespace Larafony\Framework\Encryption\Assert;

use InvalidArgumentException;

final class DataLengthIsValid
{
    public static function assert(string $data, int $minimumLength): void
    {
        if (strlen($data) < $minimumLength) {
            throw new InvalidArgumentException(
                "Data is too short. Minimum length required: {$minimumLength} bytes"
            );
        }
    }
}
