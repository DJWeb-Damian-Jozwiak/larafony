<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Clock;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\FrozenClock;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
final class FrozenClockTest extends TestCase
{
    public function testNowReturnsFrozenTime(): void
    {
        $frozenTime = new \DateTimeImmutable('2024-01-15 12:00:00');
        $clock = new FrozenClock($frozenTime);

        $this->assertEquals($frozenTime, $clock->now());
        $this->assertEquals($frozenTime, $clock->now()); // Should return same time
    }

    public function testConstructorAcceptsDateTimeImmutable(): void
    {
        $time = new \DateTimeImmutable('2024-01-15 12:00:00');
        $clock = new FrozenClock($time);

        $this->assertEquals($time, $clock->now());
    }

    public function testParse()
    {
        $clock = new FrozenClock(null);
        $clock->parse('2024-01-15 12:00:00');
        $this->assertInstanceOf(FrozenClock::class, $clock);
    }

    public function testConstructorAcceptsDateTime(): void
    {
        $time = new \DateTime('2024-01-15 12:00:00');
        $clock = new FrozenClock($time);

        $this->assertSame('2024-01-15 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testConstructorAcceptsString(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $this->assertSame('2024-01-15 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testConstructorDefaultsToCurrentTime(): void
    {
        $before = new \DateTimeImmutable('now');
        $clock = new FrozenClock();
        $after = new \DateTimeImmutable('now');

        $now = $clock->now();
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $now->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $now->getTimestamp());
    }

    public function testSetToChangesTime(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');
        $newTime = new \DateTimeImmutable('2024-06-20 15:30:00');

        $clock->withTo($newTime);

        $this->assertEquals($newTime, $clock->now());
    }

    public function testSetToAcceptsString(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->withTo('2024-06-20 15:30:00');

        $this->assertSame('2024-06-20 15:30:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testFreezeToCurrentTime(): void
    {
        $clock = new FrozenClock('2020-01-01 00:00:00');
        $before = new \DateTimeImmutable('now');
        $clock->freeze();
        $after = new \DateTimeImmutable('now');

        $now = $clock->now();
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $now->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $now->getTimestamp());
    }

    public function testTravelWithInterval(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->travel('+5 hours');

        $this->assertSame('2024-01-15 17:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testTravelSeconds(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->travelSeconds(30);

        $this->assertSame('2024-01-15 12:00:30', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testTravelMinutes(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->travelMinutes(45);

        $this->assertSame('2024-01-15 12:45:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testTravelHours(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->travelHours(3);

        $this->assertSame('2024-01-15 15:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testTravelDays(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->travelDays(10);

        $this->assertSame('2024-01-25 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testTimestampReturnsCorrectValue(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00 UTC');

        $this->assertSame(1705320000, $clock->timestamp());
    }

    public function testIsPastReturnsTrueForPastDate(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');
        $pastDate = new \DateTimeImmutable('2024-01-14 12:00:00');

        $this->assertTrue($clock->isPast($pastDate));
    }

    public function testIsPastReturnsFalseForFutureDate(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');
        $futureDate = new \DateTimeImmutable('2024-01-16 12:00:00');

        $this->assertFalse($clock->isPast($futureDate));
    }

    public function testIsFutureReturnsTrueForFutureDate(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');
        $futureDate = new \DateTimeImmutable('2024-01-16 12:00:00');

        $this->assertTrue($clock->isFuture($futureDate));
    }

    public function testIsFutureReturnsFalseForPastDate(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');
        $pastDate = new \DateTimeImmutable('2024-01-14 12:00:00');

        $this->assertFalse($clock->isFuture($pastDate));
    }

    public function testIsTodayReturnsTrueForSameDay(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');
        $sameDay = new \DateTimeImmutable('2024-01-15 18:30:00');

        $this->assertTrue($clock->isToday($sameDay));
    }

    public function testIsTodayReturnsFalseForDifferentDay(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');
        $differentDay = new \DateTimeImmutable('2024-01-16 12:00:00');

        $this->assertFalse($clock->isToday($differentDay));
    }

    public function testMultipleTravelOperations(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->travelDays(1);
        $clock->travelHours(3);
        $clock->travelMinutes(30);
        $clock->travelSeconds(45);

        $this->assertSame('2024-01-16 15:30:45', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testTravelBackwardsWithNegativeSeconds(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->travelSeconds(-30);

        $this->assertSame('2024-01-15 11:59:30', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testTravelBackwardsWithNegativeMinutes(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->travelMinutes(-15);

        $this->assertSame('2024-01-15 11:45:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testTravelBackwardsWithNegativeHours(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->travelHours(-5);

        $this->assertSame('2024-01-15 07:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testTravelBackwardsWithNegativeDays(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');

        $clock->travelDays(-10);

        $this->assertSame('2024-01-05 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testFormatWithTimeFormatEnum(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45');

        $result = $clock->format(TimeFormat::DATE);

        $this->assertSame('2024-01-15', $result);
    }

    public function testFormatWithTimeFormatEnumTime(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45');

        $result = $clock->format(TimeFormat::TIME);

        $this->assertSame('14:30:45', $result);
    }

    public function testFormatWithTimeFormatEnumDateTime(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45');

        $result = $clock->format(TimeFormat::DATETIME);

        $this->assertSame('2024-01-15 14:30:45', $result);
    }

    public function testFormatWithStringFormat(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45');

        $result = $clock->format('Y/m/d');

        $this->assertSame('2024/01/15', $result);
    }

    public function testFormatWithISO8601(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45 UTC');

        $result = $clock->format(TimeFormat::ISO8601);

        $this->assertStringStartsWith('2024-01-15T14:30:45', $result);
    }

    public function testFormatWithRFC7231(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45 UTC');

        $result = $clock->format(TimeFormat::RFC7231);

        $this->assertStringContainsString('15 Jan 2024', $result);
        $this->assertStringContainsString('GMT', $result);
    }

    public function testFormatWithDateShort(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45');

        $result = $clock->format(TimeFormat::DATE_SHORT);

        $this->assertSame('15/01/2024', $result);
    }

    public function testFormatWithTime12H(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45');

        $result = $clock->format(TimeFormat::TIME_12H);

        $this->assertSame('2:30 PM', $result);
    }

    public function testFormatWithTime24H(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45');

        $result = $clock->format(TimeFormat::TIME_24H);

        $this->assertSame('14:30', $result);
    }

    public function testFormatWithPostgresFormat(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45.123456 UTC');

        $result = $clock->format(TimeFormat::POSTGRES);

        $this->assertStringStartsWith('2024-01-15 14:30:45.', $result);
    }

    public function testFormatAfterTimeTravel(): void
    {
        $clock = new FrozenClock('2024-01-15 12:00:00');
        $clock->travelDays(5);
        $clock->travelHours(3);

        $result = $clock->format(TimeFormat::DATETIME);

        $this->assertSame('2024-01-20 15:00:00', $result);
    }

    public function testFormatMultipleTimesReturnsSameResult(): void
    {
        $clock = new FrozenClock('2024-01-15 14:30:45');

        $result1 = $clock->format(TimeFormat::DATE);
        $result2 = $clock->format(TimeFormat::DATE);

        $this->assertSame($result1, $result2);
        $this->assertSame('2024-01-15', $result1);
    }
}