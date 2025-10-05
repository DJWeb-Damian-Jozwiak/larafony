<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Clock;

use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Clock\SystemClock;
use PHPUnit\Framework\TestCase;

final class SystemClockTest extends TestCase
{
    protected function tearDown(): void
    {
        // Always clear test time after each test
        SystemClock::withTestNow();
        parent::tearDown();
    }

    public function testNowReturnsDateTimeImmutable(): void
    {
        $clock = new SystemClock();
        $now = $clock->now();

        $this->assertInstanceOf(\DateTimeImmutable::class, $now);
    }

    public function testNowReturnsCurrentTime(): void
    {
        $clock = new SystemClock();
        $before = time();
        $now = $clock->now()->getTimestamp();
        $after = time();

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }

    public function testNowRespectsTimezone(): void
    {
        $timezone = new \DateTimeZone('America/New_York');
        $clock = new SystemClock($timezone);
        $now = $clock->now();

        $this->assertSame('America/New_York', $now->getTimezone()->getName());
    }

    public function testTimestampReturnsCurrentTimestamp(): void
    {
        $clock = new SystemClock();
        $timestamp = $clock->timestamp();

        $this->assertIsInt($timestamp);
        $this->assertGreaterThan(0, $timestamp);
    }

    public function testMillisecondsReturnsCurrentMilliseconds(): void
    {
        $clock = new SystemClock();
        $ms = $clock->milliseconds();

        $this->assertIsInt($ms);
        $this->assertGreaterThan(0, $ms);
    }

    public function testMicrosecondsReturnsCurrentMicroseconds(): void
    {
        $clock = new SystemClock();
        $us = $clock->microseconds();

        $this->assertIsInt($us);
        $this->assertGreaterThan(0, $us);
    }

    public function testIsPastReturnsTrueForPastDate(): void
    {
        $clock = new SystemClock();
        $pastDate = new \DateTimeImmutable('-1 day');

        $this->assertTrue($clock->isPast($pastDate));
    }

    public function testIsPastReturnsFalseForFutureDate(): void
    {
        $clock = new SystemClock();
        $futureDate = new \DateTimeImmutable('+1 day');

        $this->assertFalse($clock->isPast($futureDate));
    }

    public function testIsFutureReturnsTrueForFutureDate(): void
    {
        $clock = new SystemClock();
        $futureDate = new \DateTimeImmutable('+1 day');

        $this->assertTrue($clock->isFuture($futureDate));
    }

    public function testIsFutureReturnsFalseForPastDate(): void
    {
        $clock = new SystemClock();
        $pastDate = new \DateTimeImmutable('-1 day');

        $this->assertFalse($clock->isFuture($pastDate));
    }

    public function testIsTodayReturnsTrueForToday(): void
    {
        $clock = new SystemClock();
        $today = new \DateTimeImmutable('now');

        $this->assertTrue($clock->isToday($today));
    }

    public function testIsTodayReturnsFalseForYesterday(): void
    {
        $clock = new SystemClock();
        $yesterday = new \DateTimeImmutable('-1 day');

        $this->assertFalse($clock->isToday($yesterday));
    }

    public function testIsTodayReturnsFalseForTomorrow(): void
    {
        $clock = new SystemClock();
        $tomorrow = new \DateTimeImmutable('+1 day');

        $this->assertFalse($clock->isToday($tomorrow));
    }

    public function testSleepPausesExecution(): void
    {
        $clock = new SystemClock();
        $start = microtime(true);
        $clock->sleep(1);
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(1.0, $elapsed);
        $this->assertLessThan(1.1, $elapsed);
    }

    public function testUsleepPausesExecution(): void
    {
        $clock = new SystemClock();
        $start = microtime(true);
        $clock->usleep(100000); // 100ms
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(0.1, $elapsed);
        $this->assertLessThan(0.2, $elapsed);
    }

    public function testSetTestNowWithDateTimeImmutable(): void
    {
        $testTime = new \DateTimeImmutable('2024-01-15 12:00:00');
        SystemClock::withTestNow($testTime);

        $clock = new SystemClock();
        $this->assertEquals($testTime, $clock->now());
    }

    public function testSetTestNowWithDateTime(): void
    {
        $testTime = new \DateTime('2024-01-15 12:00:00');
        SystemClock::withTestNow($testTime);

        $clock = new SystemClock();
        $this->assertSame('2024-01-15 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testSetTestNowWithString(): void
    {
        SystemClock::withTestNow('2024-01-15 12:00:00');

        $clock = new SystemClock();
        $this->assertSame('2024-01-15 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testSetTestNowWithNullClearsTestTime(): void
    {
        SystemClock::withTestNow('2024-01-15 12:00:00');
        SystemClock::withTestNow(null);

        $clock = new SystemClock();
        $before = time();
        $now = $clock->now()->getTimestamp();
        $after = time();

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }

    public function testSetTestNowWithoutArgumentsClearsTestTime(): void
    {
        SystemClock::withTestNow('2024-01-15 12:00:00');
        SystemClock::withTestNow();

        $clock = new SystemClock();
        $before = time();
        $now = $clock->now()->getTimestamp();
        $after = time();

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }

    public function testHasTestNowReturnsTrueWhenSet(): void
    {
        SystemClock::withTestNow('2024-01-15 12:00:00');

        $this->assertTrue(SystemClock::hasTestNow());
    }

    public function testHasTestNowReturnsFalseWhenNotSet(): void
    {
        $this->assertFalse(SystemClock::hasTestNow());
    }

    public function testWithTestNowSetsFixedTime(): void
    {
        SystemClock::withTestNow('2024-01-15 12:00:00');

        $clock = new SystemClock();
        $this->assertSame('2024-01-15 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testWithTestNowCanBeChanged(): void
    {
        SystemClock::withTestNow('2024-01-01 00:00:00');
        $clock = new SystemClock();
        $this->assertSame('2024-01-01 00:00:00', $clock->now()->format('Y-m-d H:i:s'));

        SystemClock::withTestNow('2024-06-15 12:00:00');
        $this->assertSame('2024-06-15 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testWithTestNowClearsWhenCalledWithNull(): void
    {
        SystemClock::withTestNow('2024-01-15 12:00:00');
        $this->assertTrue(SystemClock::hasTestNow());

        SystemClock::withTestNow(null);
        $this->assertFalse(SystemClock::hasTestNow());
    }

    public function testMultipleCallsToWithTestNowOverwritePreviousValue(): void
    {
        SystemClock::withTestNow('2024-01-01 00:00:00');
        SystemClock::withTestNow('2024-06-15 12:00:00');

        $clock = new SystemClock();
        $this->assertSame('2024-06-15 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testMultipleInstancesShareTestTime(): void
    {
        SystemClock::withTestNow('2024-01-15 12:00:00');

        $clock1 = new SystemClock();
        $clock2 = new SystemClock();

        $this->assertEquals($clock1->now(), $clock2->now());
        $this->assertSame('2024-01-15 12:00:00', $clock1->now()->format('Y-m-d H:i:s'));
        $this->assertSame('2024-01-15 12:00:00', $clock2->now()->format('Y-m-d H:i:s'));
    }

    public function testFromTimezoneCreatesClockWithTimezone(): void
    {
        $clock = SystemClock::fromTimezone(Timezone::EUROPE_WARSAW);

        $this->assertInstanceOf(SystemClock::class, $clock);
        $this->assertSame('Europe/Warsaw', $clock->now()->getTimezone()->getName());
    }

    public function testFromTimezoneWithNullDefaultsToUTC(): void
    {
        $clock = SystemClock::fromTimezone(null);

        $this->assertSame('UTC', $clock->now()->getTimezone()->getName());
    }

    public function testFormatWithTimeFormatEnum(): void
    {
        SystemClock::withTestNow('2024-01-15 14:30:45');
        $clock = new SystemClock();

        $result = $clock->format(TimeFormat::DATE);

        $this->assertSame('2024-01-15', $result);
    }

    public function testFormatWithTimeFormatEnumTime(): void
    {
        SystemClock::withTestNow('2024-01-15 14:30:45');
        $clock = new SystemClock();

        $result = $clock->format(TimeFormat::TIME);

        $this->assertSame('14:30:45', $result);
    }

    public function testFormatWithTimeFormatEnumDateTime(): void
    {
        SystemClock::withTestNow('2024-01-15 14:30:45');
        $clock = new SystemClock();

        $result = $clock->format(TimeFormat::DATETIME);

        $this->assertSame('2024-01-15 14:30:45', $result);
    }

    public function testFormatWithStringFormat(): void
    {
        SystemClock::withTestNow('2024-01-15 14:30:45');
        $clock = new SystemClock();

        $result = $clock->format('Y/m/d');

        $this->assertSame('2024/01/15', $result);
    }

    public function testFormatWithISO8601(): void
    {
        SystemClock::withTestNow('2024-01-15 14:30:45 UTC');
        $clock = new SystemClock(new \DateTimeZone('UTC'));

        $result = $clock->format(TimeFormat::ISO8601);

        $this->assertStringStartsWith('2024-01-15T14:30:45', $result);
    }

    public function testFormatWithRFC7231(): void
    {
        SystemClock::withTestNow('2024-01-15 14:30:45 UTC');
        $clock = new SystemClock(new \DateTimeZone('UTC'));

        $result = $clock->format(TimeFormat::RFC7231);

        $this->assertStringContainsString('15 Jan 2024', $result);
        $this->assertStringContainsString('GMT', $result);
    }

    public function testFormatWithDateShort(): void
    {
        SystemClock::withTestNow('2024-01-15 14:30:45');
        $clock = new SystemClock();

        $result = $clock->format(TimeFormat::DATE_SHORT);

        $this->assertSame('15/01/2024', $result);
    }

    public function testFormatWithTime12H(): void
    {
        SystemClock::withTestNow('2024-01-15 14:30:45');
        $clock = new SystemClock();

        $result = $clock->format(TimeFormat::TIME_12H);

        $this->assertSame('2:30 PM', $result);
    }

    public function testFormatWithTime24H(): void
    {
        SystemClock::withTestNow('2024-01-15 14:30:45');
        $clock = new SystemClock();

        $result = $clock->format(TimeFormat::TIME_24H);

        $this->assertSame('14:30', $result);
    }
}