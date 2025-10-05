<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Clock;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Clock\FrozenClock;
use Larafony\Framework\Clock\SystemClock;
use PHPUnit\Framework\TestCase;

final class ClockFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        // Always reset to default after each test
        ClockFactory::reset();
        parent::tearDown();
    }

    public function testInstanceReturnsSystemClockByDefault(): void
    {
        $clock = ClockFactory::instance();

        $this->assertInstanceOf(SystemClock::class, $clock);
    }

    public function testInstanceReturnsSameInstanceOnMultipleCalls(): void
    {
        $clock1 = ClockFactory::instance();
        $clock2 = ClockFactory::instance();

        $this->assertSame($clock1, $clock2);
    }

    public function testSetInstanceAllowsCustomClock(): void
    {
        $customClock = new FrozenClock('2024-01-15 12:00:00');
        ClockFactory::setInstance($customClock);

        $this->assertSame($customClock, ClockFactory::instance());
    }

    public function testResetClearsCustomInstance(): void
    {
        $customClock = new FrozenClock('2024-01-15 12:00:00');
        ClockFactory::setInstance($customClock);
        ClockFactory::reset();

        $clock = ClockFactory::instance();
        $this->assertInstanceOf(SystemClock::class, $clock);
        $this->assertNotSame($customClock, $clock);
    }

    public function testFreezeCreatesFrozenClock(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00');

        $clock = ClockFactory::instance();
        $this->assertInstanceOf(FrozenClock::class, $clock);
        $this->assertSame('2024-01-15 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testFreezeWithoutArgumentFreezesAtCurrentTime(): void
    {
        $before = new \DateTimeImmutable('now');
        ClockFactory::freeze();
        $after = new \DateTimeImmutable('now');

        $now = ClockFactory::now();
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $now->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $now->getTimestamp());
    }

    public function testFreezeWithDateTimeImmutable(): void
    {
        $time = new \DateTimeImmutable('2024-01-15 12:00:00');
        ClockFactory::freeze($time);

        $this->assertEquals($time, ClockFactory::now());
    }

    public function testTimezoneCreatesClockWithTimezone(): void
    {
        $clock = ClockFactory::timezone(Timezone::EUROPE_WARSAW);

        $this->assertInstanceOf(SystemClock::class, $clock);
        $this->assertSame('Europe/Warsaw', $clock->now()->getTimezone()->getName());
    }

    public function testNowReturnsCurrentTime(): void
    {
        $before = time();
        $now = ClockFactory::now();
        $after = time();

        $this->assertGreaterThanOrEqual($before, $now->getTimestamp());
        $this->assertLessThanOrEqual($after, $now->getTimestamp());
    }

    public function testNowWithFrozenClock(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00');

        $now = ClockFactory::now();
        $this->assertSame('2024-01-15 12:00:00', $now->format('Y-m-d H:i:s'));
    }

    public function testFormatWithTimeFormatEnum(): void
    {
        ClockFactory::freeze('2024-01-15 14:30:45');

        $result = ClockFactory::format(TimeFormat::DATE);

        $this->assertSame('2024-01-15', $result);
    }

    public function testFormatWithStringFormat(): void
    {
        ClockFactory::freeze('2024-01-15 14:30:45');

        $result = ClockFactory::format('Y/m/d');

        $this->assertSame('2024/01/15', $result);
    }

    public function testTimestampReturnsCurrentTimestamp(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00 UTC');

        $timestamp = ClockFactory::timestamp();

        $this->assertSame(1705320000, $timestamp);
    }

    public function testIsPastReturnsTrueForPastDate(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00');
        $pastDate = new \DateTimeImmutable('2024-01-14 12:00:00');

        $this->assertTrue(ClockFactory::isPast($pastDate));
    }

    public function testIsPastReturnsFalseForFutureDate(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00');
        $futureDate = new \DateTimeImmutable('2024-01-16 12:00:00');

        $this->assertFalse(ClockFactory::isPast($futureDate));
    }

    public function testIsFutureReturnsTrueForFutureDate(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00');
        $futureDate = new \DateTimeImmutable('2024-01-16 12:00:00');

        $this->assertTrue(ClockFactory::isFuture($futureDate));
    }

    public function testIsFutureReturnsFalseForPastDate(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00');
        $pastDate = new \DateTimeImmutable('2024-01-14 12:00:00');

        $this->assertFalse(ClockFactory::isFuture($pastDate));
    }

    public function testIsTodayReturnsTrueForSameDay(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00');
        $sameDay = new \DateTimeImmutable('2024-01-15 18:30:00');

        $this->assertTrue(ClockFactory::isToday($sameDay));
    }

    public function testIsTodayReturnsFalseForDifferentDay(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00');
        $differentDay = new \DateTimeImmutable('2024-01-16 12:00:00');

        $this->assertFalse(ClockFactory::isToday($differentDay));
    }

    public function testMockingInTestScenario(): void
    {
        // Simulate application code that uses ClockFactory
        $applicationTime = ClockFactory::now();

        // In test: freeze time
        ClockFactory::freeze('2024-01-15 12:00:00');

        // Application code now returns frozen time
        $frozenTime = ClockFactory::now();

        $this->assertSame('2024-01-15 12:00:00', $frozenTime->format('Y-m-d H:i:s'));
        $this->assertNotEquals($applicationTime->format('Y-m-d H:i:s'), $frozenTime->format('Y-m-d H:i:s'));
    }

    public function testMultipleFreezesOverwritePreviousValue(): void
    {
        ClockFactory::freeze('2024-01-01 00:00:00');
        $this->assertSame('2024-01-01 00:00:00', ClockFactory::now()->format('Y-m-d H:i:s'));

        ClockFactory::freeze('2024-06-15 12:00:00');
        $this->assertSame('2024-06-15 12:00:00', ClockFactory::now()->format('Y-m-d H:i:s'));
    }

    public function testResetAfterFreezeRestoresSystemClock(): void
    {
        ClockFactory::freeze('2024-01-15 12:00:00');
        $this->assertSame('2024-01-15 12:00:00', ClockFactory::now()->format('Y-m-d H:i:s'));

        ClockFactory::reset();

        $before = time();
        $now = ClockFactory::now()->getTimestamp();
        $after = time();

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }

    public function testCustomClockImplementationCanBeInjected(): void
    {
        $customClock = new class implements \Larafony\Framework\Clock\Contracts\Clock {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2099-12-31 23:59:59');
            }

            public function format(TimeFormat|string $format): string
            {
                return 'custom-format';
            }

            public function timestamp(): int
            {
                return 9999999999;
            }

            public function isPast(\DateTimeInterface $date): bool
            {
                return false;
            }

            public function isFuture(\DateTimeInterface $date): bool
            {
                return true;
            }

            public function isToday(\DateTimeInterface $date): bool
            {
                return false;
            }
        };

        ClockFactory::setInstance($customClock);

        $this->assertSame('2099-12-31 23:59:59', ClockFactory::now()->format('Y-m-d H:i:s'));
        $this->assertSame('custom-format', ClockFactory::format(TimeFormat::DATE));
        $this->assertSame(9999999999, ClockFactory::timestamp());
    }
}
