<?php

declare(strict_types=1);

namespace Larafony\Framework\Support;

/**
 * Utility for formatting bytes into human-readable format
 */
final class ByteFormatter
{
    private const UNITS = ['B', 'KB', 'MB', 'GB'];

    /**
     * Format bytes into human-readable string
     *
     * @param int|float $bytes Number of bytes
     * @param int $precision Decimal precision (default: 2)
     *
     * @return string Formatted string (e.g., "1.5 MB")
     */
    public static function format(int|float $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $power = floor(log($bytes, 1024));
        $power = min($power, count(self::UNITS) - 1);

        $value = $bytes / pow(1024, $power);

        return round($value, $precision) . ' ' . self::UNITS[(int) $power];
    }
}
