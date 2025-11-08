<?php

declare(strict_types=1);

namespace Larafony\Framework\Clock\Contracts;

use Larafony\Framework\Clock\Enums\TimeFormat;
use Psr\Clock\ClockInterface;

/**
 * Extended clock interface with additional formatting and comparison methods.
 *
 * Extends PSR-20 ClockInterface with convenience methods for date/time operations.
 */
interface Clock extends ClockInterface
{
    /**
     * Format the current time using TimeFormat enum or custom string.
     */
    public function format(TimeFormat|string $format): string;

    /**
     * Get current timestamp in seconds.
     */
    public function timestamp(): int;

    /**
     * Check if given date is in the past.
     */
    public function isPast(\DateTimeInterface $date): bool;

    /**
     * Check if given date is in the future.
     */
    public function isFuture(\DateTimeInterface $date): bool;

    /**
     * Check if given date is today.
     */
    public function isToday(\DateTimeInterface $date): bool;

    /**
     * Parse a date string and return a new Clock instance.
     */
    public function parse(string $date): self;
}
