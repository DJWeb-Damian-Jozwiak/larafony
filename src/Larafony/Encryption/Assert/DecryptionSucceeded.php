<?php

declare(strict_types=1);

namespace Larafony\Framework\Encryption\Assert;

use InvalidArgumentException;

final class DecryptionSucceeded
{
    /**
     * @param string|false $decrypted
     *
     * @phpstan-assert string $decrypted
     */
    public static function assert(string|false $decrypted): void
    {
        if ($decrypted === false) {
            throw new InvalidArgumentException('Decryption failed');
        }
    }
}
