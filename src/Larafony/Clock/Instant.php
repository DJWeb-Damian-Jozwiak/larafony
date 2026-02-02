<?php

declare(strict_types=1);

namespace Larafony\Framework\Clock;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Database\ORM\Contracts\Castable;
use Stringable;

/**
 * Immutable value object representing a specific instant in time.
 *
 * Provides a rich API for date/time comparisons, arithmetic operations,
 * and boundary calculations. Use toDatetime() for DateTimeInterface interoperability.
 */
final readonly class Instant implements Castable, Stringable
{
    private DateTimeImmutable $datetime;

    private function __construct(DateTimeImmutable $datetime)
    {
        $this->datetime = $datetime;
    }

    // ========================================
    // Factory Methods
    // ========================================

    /**
     * Create an Instant from a string value (Castable interface).
     */
    public static function from(string $value): static
    {
        return new self(new DateTimeImmutable($value));
    }

    /**
     * Parse a datetime string into an Instant.
     */
    public static function parse(string $value): self
    {
        return new self(new DateTimeImmutable($value));
    }

    /**
     * Create an Instant from a DateTimeInterface.
     */
    public static function fromDateTime(DateTimeInterface $datetime): self
    {
        if ($datetime instanceof DateTimeImmutable) {
            return new self($datetime);
        }

        return new self(DateTimeImmutable::createFromInterface($datetime));
    }

    /**
     * Create an Instant from a Unix timestamp.
     */
    public static function fromTimestamp(int $timestamp, ?Timezone $timezone = null): self
    {
        $tz = $timezone !== null ? new DateTimeZone($timezone->value) : null;
        $datetime = (new DateTimeImmutable('@' . $timestamp))->setTimezone($tz ?? new DateTimeZone('UTC'));

        return new self($datetime);
    }

    /**
     * Create an Instant representing the current time.
     */
    public static function now(): self
    {
        return self::fromDateTime(ClockFactory::now());
    }

    /**
     * Create an Instant from individual date/time components.
     */
    public static function create(
        int $year,
        int $month = 1,
        int $day = 1,
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
        int $microsecond = 0,
        ?Timezone $timezone = null,
    ): self {
        $tz = $timezone !== null ? new DateTimeZone($timezone->value) : null;
        $datetime = new DateTimeImmutable()
            ->setTimezone($tz ?? new DateTimeZone('UTC'))
            ->setDate($year, $month, $day)
            ->setTime($hour, $minute, $second, $microsecond);

        return new self($datetime);
    }

    // ========================================
    // Comparison Methods
    // ========================================

    /**
     * Check if this instant is before another.
     */
    public function isBefore(DateTimeInterface|self $other): bool
    {
        return $this->datetime < $this->toDateTimeInterface($other);
    }

    /**
     * Check if this instant is after another.
     */
    public function isAfter(DateTimeInterface|self $other): bool
    {
        return $this->datetime > $this->toDateTimeInterface($other);
    }

    /**
     * Check if this instant is before or equal to another.
     */
    public function isBeforeOrEqual(DateTimeInterface|self $other): bool
    {
        return $this->datetime <= $this->toDateTimeInterface($other);
    }

    /**
     * Check if this instant is after or equal to another.
     */
    public function isAfterOrEqual(DateTimeInterface|self $other): bool
    {
        return $this->datetime >= $this->toDateTimeInterface($other);
    }

    /**
     * Check if this instant equals another.
     */
    public function equals(DateTimeInterface|self $other): bool
    {
        return $this->datetime == $this->toDateTimeInterface($other);
    }

    /**
     * Check if this instant is between two others.
     */
    public function isBetween(DateTimeInterface|self $start, DateTimeInterface|self $end, bool $inclusive = true): bool
    {
        $startDt = $this->toDateTimeInterface($start);
        $endDt = $this->toDateTimeInterface($end);

        if ($inclusive) {
            return $this->datetime >= $startDt && $this->datetime <= $endDt;
        }

        return $this->datetime > $startDt && $this->datetime < $endDt;
    }

    /**
     * Check if this instant is in the past.
     */
    public function isPast(): bool
    {
        return $this->datetime < ClockFactory::now();
    }

    /**
     * Check if this instant is in the future.
     */
    public function isFuture(): bool
    {
        return $this->datetime > ClockFactory::now();
    }

    /**
     * Check if this instant is today.
     */
    public function isToday(): bool
    {
        return $this->datetime->format('Y-m-d') === ClockFactory::now()->format('Y-m-d');
    }

    /**
     * Check if this instant is tomorrow.
     */
    public function isTomorrow(): bool
    {
        return $this->datetime->format('Y-m-d') === ClockFactory::now()->modify('+1 day')->format('Y-m-d');
    }

    /**
     * Check if this instant is yesterday.
     */
    public function isYesterday(): bool
    {
        return $this->datetime->format('Y-m-d') === ClockFactory::now()->modify('-1 day')->format('Y-m-d');
    }

    // ========================================
    // Arithmetic Methods (return new Instant)
    // ========================================

    /**
     * Add seconds to this instant.
     */
    public function addSeconds(int $seconds): self
    {
        return new self($this->datetime->modify("+{$seconds} seconds"));
    }

    /**
     * Add minutes to this instant.
     */
    public function addMinutes(int $minutes): self
    {
        return new self($this->datetime->modify("+{$minutes} minutes"));
    }

    /**
     * Add hours to this instant.
     */
    public function addHours(int $hours): self
    {
        return new self($this->datetime->modify("+{$hours} hours"));
    }

    /**
     * Add days to this instant.
     */
    public function addDays(int $days): self
    {
        return new self($this->datetime->modify("+{$days} days"));
    }

    /**
     * Add weeks to this instant.
     */
    public function addWeeks(int $weeks): self
    {
        return new self($this->datetime->modify("+{$weeks} weeks"));
    }

    /**
     * Add months to this instant.
     */
    public function addMonths(int $months): self
    {
        return new self($this->datetime->modify("+{$months} months"));
    }

    /**
     * Add years to this instant.
     */
    public function addYears(int $years): self
    {
        return new self($this->datetime->modify("+{$years} years"));
    }

    /**
     * Subtract seconds from this instant.
     */
    public function subSeconds(int $seconds): self
    {
        return new self($this->datetime->modify("-{$seconds} seconds"));
    }

    /**
     * Subtract minutes from this instant.
     */
    public function subMinutes(int $minutes): self
    {
        return new self($this->datetime->modify("-{$minutes} minutes"));
    }

    /**
     * Subtract hours from this instant.
     */
    public function subHours(int $hours): self
    {
        return new self($this->datetime->modify("-{$hours} hours"));
    }

    /**
     * Subtract days from this instant.
     */
    public function subDays(int $days): self
    {
        return new self($this->datetime->modify("-{$days} days"));
    }

    /**
     * Subtract weeks from this instant.
     */
    public function subWeeks(int $weeks): self
    {
        return new self($this->datetime->modify("-{$weeks} weeks"));
    }

    /**
     * Subtract months from this instant.
     */
    public function subMonths(int $months): self
    {
        return new self($this->datetime->modify("-{$months} months"));
    }

    /**
     * Subtract years from this instant.
     */
    public function subYears(int $years): self
    {
        return new self($this->datetime->modify("-{$years} years"));
    }

    /**
     * Add a DateInterval to this instant.
     */
    public function add(DateInterval $interval): self
    {
        return new self($this->datetime->add($interval));
    }

    /**
     * Subtract a DateInterval from this instant.
     */
    public function sub(DateInterval $interval): self
    {
        return new self($this->datetime->sub($interval));
    }

    /**
     * Modify this instant using a relative string format.
     */
    public function modify(string $modifier): self
    {
        return new self($this->datetime->modify($modifier));
    }

    // ========================================
    // Difference Methods
    // ========================================

    /**
     * Get the difference between this instant and another as a DateInterval.
     */
    public function diff(DateTimeInterface|self $other, bool $absolute = false): DateInterval
    {
        return $this->datetime->diff($this->toDateTimeInterface($other), $absolute);
    }

    /**
     * Get the difference in seconds.
     */
    public function diffInSeconds(DateTimeInterface|self $other): int
    {
        return $this->toTimestamp() - $this->toDateTimeInterface($other)->getTimestamp();
    }

    /**
     * Get the difference in minutes.
     */
    public function diffInMinutes(DateTimeInterface|self $other): int
    {
        return (int) floor($this->diffInSeconds($other) / 60);
    }

    /**
     * Get the difference in hours.
     */
    public function diffInHours(DateTimeInterface|self $other): int
    {
        return (int) floor($this->diffInSeconds($other) / 3600);
    }

    /**
     * Get the difference in days.
     */
    public function diffInDays(DateTimeInterface|self $other): int
    {
        return (int) floor($this->diffInSeconds($other) / 86400);
    }

    // ========================================
    // Boundary Methods
    // ========================================

    /**
     * Get the start of day (00:00:00).
     */
    public function startOfDay(): self
    {
        return new self($this->datetime->setTime(0, 0, 0, 0));
    }

    /**
     * Get the end of day (23:59:59.999999).
     */
    public function endOfDay(): self
    {
        return new self($this->datetime->setTime(23, 59, 59, 999999));
    }

    /**
     * Get the start of week (Monday 00:00:00).
     */
    public function startOfWeek(): self
    {
        $dayOfWeek = (int) $this->datetime->format('N'); // 1 = Monday, 7 = Sunday
        $daysToSubtract = $dayOfWeek - 1;

        return new self($this->datetime->modify("-{$daysToSubtract} days")->setTime(0, 0, 0, 0));
    }

    /**
     * Get the end of week (Sunday 23:59:59.999999).
     */
    public function endOfWeek(): self
    {
        $dayOfWeek = (int) $this->datetime->format('N');
        $daysToAdd = 7 - $dayOfWeek;

        return new self($this->datetime->modify("+{$daysToAdd} days")->setTime(23, 59, 59, 999999));
    }

    /**
     * Get the start of month (1st day 00:00:00).
     */
    public function startOfMonth(): self
    {
        return new self($this->datetime->modify('first day of this month')->setTime(0, 0, 0, 0));
    }

    /**
     * Get the end of month (last day 23:59:59.999999).
     */
    public function endOfMonth(): self
    {
        return new self($this->datetime->modify('last day of this month')->setTime(23, 59, 59, 999999));
    }

    /**
     * Get the start of year (January 1st 00:00:00).
     */
    public function startOfYear(): self
    {
        return new self($this->datetime->modify('first day of January')->setTime(0, 0, 0, 0));
    }

    /**
     * Get the end of year (December 31st 23:59:59.999999).
     */
    public function endOfYear(): self
    {
        return new self($this->datetime->modify('last day of December')->setTime(23, 59, 59, 999999));
    }

    // ========================================
    // Formatting Methods
    // ========================================

    /**
     * Format this instant using a TimeFormat enum or custom string.
     */
    public function format(TimeFormat|string $format): string
    {
        $formatString = $format instanceof TimeFormat ? $format->value : $format;

        return $this->datetime->format($formatString);
    }

    /**
     * Convert to DateTimeImmutable.
     */
    public function toDatetime(): DateTimeImmutable
    {
        return $this->datetime;
    }

    /**
     * Convert to Unix timestamp.
     */
    public function toTimestamp(): int
    {
        return $this->datetime->getTimestamp();
    }

    /**
     * Convert an Instant to a datetime string (static method for CastUsing castBack).
     */
    public static function toDatetimeString(self $instant): string
    {
        return $instant->format(TimeFormat::DATETIME);
    }

    /**
     * Convert to ISO8601 string.
     */
    public function __toString(): string
    {
        return $this->datetime->format(DateTimeInterface::ATOM);
    }

    // ========================================
    // DateTime-like Accessors
    // ========================================

    /**
     * Get the timezone of this instant.
     */
    public function getTimezone(): DateTimeZone
    {
        /** @var DateTimeZone $timezone */
        $timezone = $this->datetime->getTimezone();

        return $timezone;
    }

    /**
     * Get the UTC offset in seconds.
     */
    public function getOffset(): int
    {
        return $this->datetime->getOffset();
    }

    /**
     * Get the Unix timestamp.
     */
    public function getTimestamp(): int
    {
        return $this->datetime->getTimestamp();
    }

    /**
     * Get the microsecond component.
     */
    public function getMicrosecond(): int
    {
        return (int) $this->datetime->format('u');
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Convert input to DateTimeInterface.
     */
    private function toDateTimeInterface(DateTimeInterface|self $value): DateTimeInterface
    {
        return $value instanceof self ? $value->datetime : $value;
    }
}
