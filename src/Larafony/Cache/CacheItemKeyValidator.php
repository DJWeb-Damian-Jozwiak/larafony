<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache;

final class CacheItemKeyValidator
{
    /**
     * Validate cache key according to PSR-6 specification
     *
     * @param string $key
     *
     * @return void
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function validate(string $key): void
    {
        // PSR-6: Key MUST NOT contain: {}()/\@:
        if (preg_match('/[{}()\\/\\\\@:]/', $key)) {
            throw new \InvalidArgumentException(
                "Cache key \"{$key}\" contains invalid characters. " .
                'Reserved characters are: {}()/\\@:'
            );
        }

        // Reasonable length limit
        if (strlen($key) > 64) {
            throw new \InvalidArgumentException(
                "Cache key \"{$key}\" is too long (max 64 characters)"
            );
        }

        // Must not be empty
        if ($key === '') {
            throw new \InvalidArgumentException(
                'Cache key cannot be empty'
            );
        }
    }
}
