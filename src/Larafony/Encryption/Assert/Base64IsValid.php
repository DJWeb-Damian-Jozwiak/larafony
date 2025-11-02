<?php

declare(strict_types=1);

namespace Larafony\Framework\Encryption\Assert;

use InvalidArgumentException;

final class Base64IsValid
{
    /**
     * @param string|false $decoded
     *
     * @phpstan-assert string $decoded
     */
    public static function assert(string|false $decoded): void
    {
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64 encoding');
        }
    }
}
