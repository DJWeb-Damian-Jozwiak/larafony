<?php

declare(strict_types=1);

namespace Larafony\Framework\Encryption\Assert;

use InvalidArgumentException;

final class KeyLengthIsValid
{
    public static function assert(string $decodedKey, int $expectedLength): void
    {
        if ($decodedKey === '' || strlen($decodedKey) !== $expectedLength) {
            throw new InvalidArgumentException(
                "Invalid encryption key. Key must be exactly {$expectedLength} bytes when decoded."
            );
        }
    }
}
