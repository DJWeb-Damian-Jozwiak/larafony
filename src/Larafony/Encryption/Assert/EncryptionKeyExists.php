<?php

declare(strict_types=1);

namespace Larafony\Framework\Encryption\Assert;

use RuntimeException;

final class EncryptionKeyExists
{
    public static function assert(?string $key): void
    {
        if (! $key) {
            throw new RuntimeException(
                'No encryption key set. Run "php bin/console key:generate"'
            );
        }
    }
}
