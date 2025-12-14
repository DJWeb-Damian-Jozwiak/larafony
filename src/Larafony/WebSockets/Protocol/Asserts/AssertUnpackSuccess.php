<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol\Asserts;

use RuntimeException;

final class AssertUnpackSuccess
{
    /**
     * Asserts that unpack() returned a valid array.
     *
     * @param array<int, int>|false $result The result from unpack()
     *
     * @return array<int, int> The validated array
     *
     * @throws RuntimeException When unpack failed
     */
    public static function assert(array|false $result): array
    {
        if ($result === false) {
            throw new RuntimeException('Failed to unpack WebSocket frame data');
        }

        return $result;
    }
}
