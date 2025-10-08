<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Support;

use RuntimeException;

/**
 * Guard for property hooks that should not be accessed when object is in inactive state.
 *
 * Used with PHP 8.4+ property hooks to prevent access to detached/inactive objects.
 */
final class InactivePropertyGuard
{
    /**
     * Get property value or throw exception if object is inactive.
     *
     * @template T
     *
     * @param T $value The property value
     * @param bool $isInactive Whether the object is in inactive state
     * @param string $message Error message to throw
     *
     * @return T
     *
     * @throws RuntimeException If object is inactive
     */
    public static function get(mixed $value, bool $isInactive, string $message): mixed
    {
        if ($isInactive) {
            throw new RuntimeException($message);
        }

        return $value;
    }
}
