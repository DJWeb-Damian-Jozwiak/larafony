<?php

declare(strict_types=1);

namespace Larafony\Framework\Clock;

use Larafony\Framework\Clock\Contracts\Clock;
use Larafony\Framework\Clock\Enums\TimeFormat;

/**
 * Frozen clock implementation for testing - allows setting fixed time.
 */
final class FrozenClock implements Clock
{
    private \DateTimeImmutable $frozenTime;

    public function __construct(
        \DateTimeImmutable|\DateTimeInterface|string|null $frozenTime = null,
    ) {
        $this->frozenTime = match (true) {
            $frozenTime instanceof \DateTimeImmutable => $frozenTime,
            $frozenTime instanceof \DateTimeInterface => \DateTimeImmutable::createFromInterface($frozenTime),
            is_string($frozenTime) => new \DateTimeImmutable($frozenTime),
            default => new \DateTimeImmutable('now'),
        };
    }

    public function format(TimeFormat|string $format): string
    {
        $format = is_string($format) ? $format : $format->value;
        return $this->frozenTime->format($format);
    }

    public function now(): \DateTimeImmutable
    {
        return $this->frozenTime;
    }

    /**
     * Set the frozen time.
     */
    public function withTo(\DateTimeImmutable|\DateTimeInterface|string $time): void
    {
        $this->frozenTime = match (true) {
            $time instanceof \DateTimeImmutable => $time,
            $time instanceof \DateTimeInterface => \DateTimeImmutable::createFromInterface($time),
            default => new \DateTimeImmutable($time),
        };
    }

    /**
     * Freeze at current system time.
     */
    public function freeze(): void
    {
        $this->frozenTime = new \DateTimeImmutable('now');
    }

    /**
     * Travel forward in time.
     */
    public function travel(string $interval): void
    {
        $this->frozenTime = $this->frozenTime->modify($interval);
    }

    /**
     * Travel by given seconds (positive or negative).
     */
    public function travelSeconds(int $seconds): void
    {
        $this->frozenTime = $this->frozenTime->modify($this->sign($seconds) . "{$seconds} seconds");
    }

    /**
     * Travel by given minutes (positive or negative).
     */
    public function travelMinutes(int $minutes): void
    {
        $this->frozenTime = $this->frozenTime->modify($this->sign($minutes) . "{$minutes} minutes");
    }

    /**
     * Travel by given hours (positive or negative).
     */
    public function travelHours(int $hours): void
    {
        $this->frozenTime = $this->frozenTime->modify($this->sign($hours) . "{$hours} hours");
    }

    /**
     * Travel by given days (positive or negative).
     */
    public function travelDays(int $days): void
    {
        $this->frozenTime = $this->frozenTime->modify($this->sign($days) . "{$days} days");
    }

    /**
     * Get current timestamp in seconds.
     */
    public function timestamp(): int
    {
        return $this->frozenTime->getTimestamp();
    }

    /**
     * Check if given date is in the past.
     */
    public function isPast(\DateTimeInterface $date): bool
    {
        return $date < $this->frozenTime;
    }

    /**
     * Check if given date is in the future.
     */
    public function isFuture(\DateTimeInterface $date): bool
    {
        return $date > $this->frozenTime;
    }

    /**
     * Check if given date is today.
     */
    public function isToday(\DateTimeInterface $date): bool
    {
        return $date->format('Y-m-d') === $this->frozenTime->format('Y-m-d');
    }

    /**
     * Parse a date string and return a new Clock instance.
     */
    public function parse(string $date): self
    {
        return new self($date);
    }

    /**
     * Get sign prefix for time modification.
     */
    private function sign(int $value): string
    {
        return $value >= 0 ? '+' : '';
    }
}
