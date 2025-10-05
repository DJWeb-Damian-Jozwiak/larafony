<?php

declare(strict_types=1);

namespace Larafony\Framework\Clock;

use Larafony\Framework\Clock\Contracts\Clock;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Psr\Clock\ClockInterface;

/**
 * Clock factory with strategy pattern for easy testing.
 *
 * Allows swapping clock implementation globally for tests without using static methods directly.
 */
final class ClockFactory
{
    private static (Clock & ClockInterface)|null $instance = null;

    /**
     * Get the current clock instance (creates SystemClock if not set).
     */
    public static function instance(): Clock
    {
        return self::$instance ??= new SystemClock();
    }

    /**
     * Set a custom clock instance (useful for testing).
     */
    public static function setInstance(Clock $clock): void
    {
        self::$instance = $clock;
    }

    /**
     * Reset to default SystemClock.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Freeze time at a specific moment (creates FrozenClock).
     */
    public static function freeze(\DateTimeImmutable|\DateTimeInterface|string|null $time = null): void
    {
        self::$instance = new FrozenClock($time);
    }

    /**
     * Create a clock with a specific timezone.
     */
    public static function timezone(Timezone $timezone): Clock
    {
        return SystemClock::fromTimezone($timezone);
    }

    /**
     * Get current time.
     */
    public static function now(): \DateTimeImmutable
    {
        return self::instance()->now();
    }

    /**
     * Format current time.
     */
    public static function format(TimeFormat|string $format): string
    {
        return self::instance()->format($format);
    }

    /**
     * Get current timestamp.
     */
    public static function timestamp(): int
    {
        return self::instance()->timestamp();
    }

    /**
     * Check if date is in the past.
     */
    public static function isPast(\DateTimeInterface $date): bool
    {
        return self::instance()->isPast($date);
    }

    /**
     * Check if date is in the future.
     */
    public static function isFuture(\DateTimeInterface $date): bool
    {
        return self::instance()->isFuture($date);
    }

    /**
     * Check if date is today.
     */
    public static function isToday(\DateTimeInterface $date): bool
    {
        return self::instance()->isToday($date);
    }
}
