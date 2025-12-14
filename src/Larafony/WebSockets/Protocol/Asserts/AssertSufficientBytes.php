<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol\Asserts;

use RuntimeException;

final class AssertSufficientBytes
{
    public const int EXTENDED_LENGTH_16BIT = 2;
    public const int EXTENDED_LENGTH_64BIT = 8;
    public const int MASKING_KEY = 4;

    /**
     * Asserts that the byte array has sufficient elements.
     *
     * @param array<int, int> $bytes The byte array to check
     * @param int $required The minimum required count
     * @param string $context Description of what the bytes are for
     *
     * @throws RuntimeException When there are insufficient bytes
     */
    public static function assert(array $bytes, int $required, string $context): void
    {
        if (count($bytes) < $required) {
            throw new RuntimeException(
                sprintf(
                    'Insufficient data for %s: expected at least %d bytes, got %d',
                    $context,
                    $required,
                    count($bytes)
                )
            );
        }
    }

    /**
     * Asserts sufficient bytes for 16-bit extended payload length.
     *
     * @param array<int, int> $bytes
     */
    public static function forExtendedLength16(array $bytes): void
    {
        self::assert($bytes, self::EXTENDED_LENGTH_16BIT, 'extended payload length (16-bit)');
    }

    /**
     * Asserts sufficient bytes for 64-bit extended payload length.
     *
     * @param array<int, int> $bytes
     */
    public static function forExtendedLength64(array $bytes): void
    {
        self::assert($bytes, self::EXTENDED_LENGTH_64BIT, 'extended payload length (64-bit)');
    }

    /**
     * Asserts sufficient bytes for masking key.
     *
     * @param array<int, int> $bytes
     */
    public static function forMaskingKey(array $bytes): void
    {
        self::assert($bytes, self::MASKING_KEY, 'masking key');
    }
}
