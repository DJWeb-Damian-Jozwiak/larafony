<?php

declare(strict_types=1);

namespace Larafony\Framework\Clock;

use DateTimeZone;
use Larafony\Framework\Clock\Contracts\Clock;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Database\ORM\Contracts\Castable;

/**
 * System clock implementation using real system time.
 *
 * Compatible with Carbon's testing API via setTestNow().
 */
final class SystemClock implements Clock, Castable
{
    private static ?\DateTimeImmutable $testNow = null;

    public function __construct(
        private readonly ?\DateTimeZone $timezone = null,
        private readonly ?\DateTimeImmutable $frozenTime = null,
    ) {
    }

    public static function fromTimezone(?Timezone $timezone = null): self
    {
        return new self(new DateTimeZone($timezone->value ?? 'UTC'));
    }

    /**
     * Create SystemClock from datetime string (Castable interface).
     *
     * @param string $value DateTime string to parse
     *
     * @return static
     */
    public static function from(string $value): static
    {
        $datetime = new \DateTimeImmutable($value);
        // Create a frozen clock instance that represents this specific time
        // without affecting the global time (uses instance-level frozen time)
        return new self($datetime->getTimezone(), $datetime);
    }

    public function format(TimeFormat|string $format): string
    {
        $format = is_string($format) ? $format : $format->value;
        return $this->now()->format($format);
    }

    public function now(): \DateTimeImmutable
    {
        // Priority: instance frozen time > global test time > real time
        return $this->frozenTime ?? self::$testNow ?? new \DateTimeImmutable('now', $this->timezone);
    }

    /**
     * Set a fixed time for testing (Carbon-compatible API).
     *
     * @param \DateTimeImmutable|\DateTimeInterface|string|null $testNow
     */
    public static function withTestNow(\DateTimeImmutable|\DateTimeInterface|string|null $testNow = null): void
    {
        self::$testNow = match (true) {
            $testNow === null => null,
            $testNow instanceof \DateTimeImmutable => $testNow,
            $testNow instanceof \DateTimeInterface => \DateTimeImmutable::createFromInterface($testNow),
            default => new \DateTimeImmutable($testNow),
        };
    }

    /**
     * Check if test time is set (Carbon-compatible API).
     */
    public static function hasTestNow(): bool
    {
        return self::$testNow !== null;
    }

    /**
     * Get current timestamp in seconds.
     */
    public function timestamp(): int
    {
        return $this->now()->getTimestamp();
    }

    /**
     * Get current timestamp in milliseconds.
     */
    public function milliseconds(): int
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * Get current timestamp in microseconds.
     */
    public function microseconds(): int
    {
        return (int) (microtime(true) * 1000000);
    }

    /**
     * Check if given date is in the past.
     */
    public function isPast(\DateTimeInterface $date): bool
    {
        return $date < $this->now();
    }

    /**
     * Check if given date is in the future.
     */
    public function isFuture(\DateTimeInterface $date): bool
    {
        return $date > $this->now();
    }

    /**
     * Check if given date is today.
     */
    public function isToday(\DateTimeInterface $date): bool
    {
        return $date->format('Y-m-d') === $this->now()->format('Y-m-d');
    }

    /**
     * Sleep for given seconds.
     */
    public function sleep(int $seconds): void
    {
        sleep($seconds);
    }

    /**
     * Sleep for given microseconds.
     */
    public function usleep(int $microseconds): void
    {
        usleep($microseconds);
    }

    /**
     * Parse a date string and return a new Clock instance.
     *
     * Note: This creates a NEW clock instance representing the parsed time,
     * it does NOT freeze the global time.
     */
    public function parse(string $date): self
    {
        $datetime = new \DateTimeImmutable($date);
        // Create a frozen clock instance without affecting global time
        return new self($datetime->getTimezone(), $datetime);
    }
}
