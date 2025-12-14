<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol\Asserts;

use RuntimeException;

final class AssertMinimumFrameLength
{
    private const MINIMUM_FRAME_BYTES = 2;

    /**
     * Asserts that the data has minimum required length for a WebSocket frame.
     *
     * @throws RuntimeException When data is too short
     */
    public static function assert(string $data): void
    {
        if (strlen($data) < self::MINIMUM_FRAME_BYTES) {
            throw new RuntimeException(
                sprintf(
                    'Insufficient data for WebSocket frame: expected at least %d bytes, got %d',
                    self::MINIMUM_FRAME_BYTES,
                    strlen($data)
                )
            );
        }
    }
}
