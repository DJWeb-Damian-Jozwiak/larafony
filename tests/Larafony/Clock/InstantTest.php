<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Clock;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Clock\Instant;
use Larafony\Framework\Clock\SystemClock;
use Larafony\Framework\Database\ORM\Contracts\Castable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Instant::class)]
final class InstantTest extends TestCase
{
    protected function setUp(): void
    {
        ClockFactory::freeze('2024-06-15 12:00:00');
    }

    protected function tearDown(): void
    {
        ClockFactory::reset();
        SystemClock::withTestNow();
        parent::tearDown();
    }

    // ========================================
    // Factory Tests
    // ========================================

    public function testFromCreatesInstantFromString(): void
    {
        $instant = Instant::from('2024-01-15 10:30:00');

        $this->assertInstanceOf(Instant::class, $instant);
        $this->assertSame('2024-01-15 10:30:00', $instant->format(TimeFormat::DATETIME));
    }

    public function testFromImplementsCastableInterface(): void
    {
        $this->assertTrue(is_subclass_of(Instant::class, Castable::class));
    }

    public function testParseCreatesInstantFromString(): void
    {
        $instant = Instant::parse('2024-03-20 15:45:30');

        $this->assertSame('2024-03-20 15:45:30', $instant->format(TimeFormat::DATETIME));
    }

    public function testFromDateTimeWithDateTimeImmutable(): void
    {
        $datetime = new DateTimeImmutable('2024-02-28 08:00:00');
        $instant = Instant::fromDateTime($datetime);

        $this->assertSame('2024-02-28 08:00:00', $instant->format(TimeFormat::DATETIME));
    }

    public function testFromDateTimeWithDateTime(): void
    {
        $datetime = new \DateTime('2024-04-10 16:20:00');
        $instant = Instant::fromDateTime($datetime);

        $this->assertSame('2024-04-10 16:20:00', $instant->format(TimeFormat::DATETIME));
    }

    public function testFromTimestampCreatesInstant(): void
    {
        $timestamp = 1705320600; // 2024-01-15 12:30:00 UTC
        $instant = Instant::fromTimestamp($timestamp);

        $this->assertSame($timestamp, $instant->toTimestamp());
    }

    public function testFromTimestampWithTimezone(): void
    {
        $timestamp = 1705320600;
        $instant = Instant::fromTimestamp($timestamp, Timezone::EUROPE_WARSAW);

        $this->assertSame('Europe/Warsaw', $instant->getTimezone()->getName());
    }

    public function testNowReturnsCurrentTime(): void
    {
        $instant = Instant::now();

        $this->assertSame('2024-06-15 12:00:00', $instant->format(TimeFormat::DATETIME));
    }

    public function testCreateWithAllComponents(): void
    {
        $instant = Instant::create(2024, 7, 4, 14, 30, 45, 123456);

        $this->assertSame('2024-07-04', $instant->format(TimeFormat::DATE));
        $this->assertSame('14:30:45', $instant->format(TimeFormat::TIME));
    }

    public function testCreateWithDefaults(): void
    {
        $instant = Instant::create(2024);

        $this->assertSame('2024-01-01 00:00:00', $instant->format(TimeFormat::DATETIME));
    }

    public function testCreateWithTimezone(): void
    {
        $instant = Instant::create(2024, 5, 1, timezone: Timezone::AMERICA_NEW_YORK);

        $this->assertSame('America/New_York', $instant->getTimezone()->getName());
    }

    // ========================================
    // Comparison Tests
    // ========================================

    public function testIsBefore(): void
    {
        $earlier = Instant::parse('2024-01-01 10:00:00');
        $later = Instant::parse('2024-01-01 12:00:00');

        $this->assertTrue($earlier->isBefore($later));
        $this->assertFalse($later->isBefore($earlier));
    }

    public function testIsBeforeWithDateTimeInterface(): void
    {
        $instant = Instant::parse('2024-01-01 10:00:00');
        $datetime = new DateTimeImmutable('2024-01-01 12:00:00');

        $this->assertTrue($instant->isBefore($datetime));
    }

    public function testIsAfter(): void
    {
        $earlier = Instant::parse('2024-01-01 10:00:00');
        $later = Instant::parse('2024-01-01 12:00:00');

        $this->assertTrue($later->isAfter($earlier));
        $this->assertFalse($earlier->isAfter($later));
    }

    public function testIsAfterWithDateTimeInterface(): void
    {
        $instant = Instant::parse('2024-01-01 14:00:00');
        $datetime = new DateTimeImmutable('2024-01-01 12:00:00');

        $this->assertTrue($instant->isAfter($datetime));
    }

    public function testIsBeforeOrEqual(): void
    {
        $instant1 = Instant::parse('2024-01-01 10:00:00');
        $instant2 = Instant::parse('2024-01-01 10:00:00');
        $instant3 = Instant::parse('2024-01-01 12:00:00');

        $this->assertTrue($instant1->isBeforeOrEqual($instant2));
        $this->assertTrue($instant1->isBeforeOrEqual($instant3));
        $this->assertFalse($instant3->isBeforeOrEqual($instant1));
    }

    public function testIsAfterOrEqual(): void
    {
        $instant1 = Instant::parse('2024-01-01 14:00:00');
        $instant2 = Instant::parse('2024-01-01 14:00:00');
        $instant3 = Instant::parse('2024-01-01 10:00:00');

        $this->assertTrue($instant1->isAfterOrEqual($instant2));
        $this->assertTrue($instant1->isAfterOrEqual($instant3));
        $this->assertFalse($instant3->isAfterOrEqual($instant1));
    }

    public function testEquals(): void
    {
        $instant1 = Instant::parse('2024-01-01 10:00:00');
        $instant2 = Instant::parse('2024-01-01 10:00:00');
        $instant3 = Instant::parse('2024-01-01 10:00:01');

        $this->assertTrue($instant1->equals($instant2));
        $this->assertFalse($instant1->equals($instant3));
    }

    public function testEqualsWithDateTimeInterface(): void
    {
        $instant = Instant::parse('2024-01-01 10:00:00');
        $datetime = new DateTimeImmutable('2024-01-01 10:00:00');

        $this->assertTrue($instant->equals($datetime));
    }

    public function testIsBetweenInclusive(): void
    {
        $start = Instant::parse('2024-01-01 10:00:00');
        $middle = Instant::parse('2024-01-01 12:00:00');
        $end = Instant::parse('2024-01-01 14:00:00');

        $this->assertTrue($middle->isBetween($start, $end));
        $this->assertTrue($start->isBetween($start, $end)); // inclusive start
        $this->assertTrue($end->isBetween($start, $end));   // inclusive end
    }

    public function testIsBetweenExclusive(): void
    {
        $start = Instant::parse('2024-01-01 10:00:00');
        $middle = Instant::parse('2024-01-01 12:00:00');
        $end = Instant::parse('2024-01-01 14:00:00');

        $this->assertTrue($middle->isBetween($start, $end, inclusive: false));
        $this->assertFalse($start->isBetween($start, $end, inclusive: false));
        $this->assertFalse($end->isBetween($start, $end, inclusive: false));
    }

    public function testIsBetweenWithDateTimeInterface(): void
    {
        $instant = Instant::parse('2024-01-01 12:00:00');
        $start = new DateTimeImmutable('2024-01-01 10:00:00');
        $end = new DateTimeImmutable('2024-01-01 14:00:00');

        $this->assertTrue($instant->isBetween($start, $end));
    }

    public function testIsPast(): void
    {
        $past = Instant::parse('2024-01-01 10:00:00');
        $future = Instant::parse('2024-12-31 10:00:00');

        $this->assertTrue($past->isPast());
        $this->assertFalse($future->isPast());
    }

    public function testIsFuture(): void
    {
        $past = Instant::parse('2024-01-01 10:00:00');
        $future = Instant::parse('2024-12-31 10:00:00');

        $this->assertFalse($past->isFuture());
        $this->assertTrue($future->isFuture());
    }

    public function testIsToday(): void
    {
        $today = Instant::parse('2024-06-15 08:30:00');
        $yesterday = Instant::parse('2024-06-14 08:30:00');

        $this->assertTrue($today->isToday());
        $this->assertFalse($yesterday->isToday());
    }

    public function testIsTomorrow(): void
    {
        $tomorrow = Instant::parse('2024-06-16 08:30:00');
        $today = Instant::parse('2024-06-15 08:30:00');

        $this->assertTrue($tomorrow->isTomorrow());
        $this->assertFalse($today->isTomorrow());
    }

    public function testIsYesterday(): void
    {
        $yesterday = Instant::parse('2024-06-14 08:30:00');
        $today = Instant::parse('2024-06-15 08:30:00');

        $this->assertTrue($yesterday->isYesterday());
        $this->assertFalse($today->isYesterday());
    }

    // ========================================
    // Arithmetic Tests
    // ========================================

    public function testAddSecondsReturnsNewInstant(): void
    {
        $original = Instant::parse('2024-01-01 10:00:00');
        $result = $original->addSeconds(30);

        $this->assertSame('2024-01-01 10:00:30', $result->format(TimeFormat::DATETIME));
        $this->assertSame('2024-01-01 10:00:00', $original->format(TimeFormat::DATETIME)); // immutability
    }

    public function testAddMinutes(): void
    {
        $instant = Instant::parse('2024-01-01 10:00:00');

        $this->assertSame('2024-01-01 10:45:00', $instant->addMinutes(45)->format(TimeFormat::DATETIME));
    }

    public function testAddHours(): void
    {
        $instant = Instant::parse('2024-01-01 10:00:00');

        $this->assertSame('2024-01-01 16:00:00', $instant->addHours(6)->format(TimeFormat::DATETIME));
    }

    public function testAddDays(): void
    {
        $instant = Instant::parse('2024-01-01 10:00:00');

        $this->assertSame('2024-01-08 10:00:00', $instant->addDays(7)->format(TimeFormat::DATETIME));
    }

    public function testAddWeeks(): void
    {
        $instant = Instant::parse('2024-01-01 10:00:00');

        $this->assertSame('2024-01-15 10:00:00', $instant->addWeeks(2)->format(TimeFormat::DATETIME));
    }

    public function testAddMonths(): void
    {
        $instant = Instant::parse('2024-01-15 10:00:00');

        $this->assertSame('2024-04-15 10:00:00', $instant->addMonths(3)->format(TimeFormat::DATETIME));
    }

    public function testAddYears(): void
    {
        $instant = Instant::parse('2024-01-01 10:00:00');

        $this->assertSame('2026-01-01 10:00:00', $instant->addYears(2)->format(TimeFormat::DATETIME));
    }

    public function testSubSecondsReturnsNewInstant(): void
    {
        $original = Instant::parse('2024-01-01 10:00:30');
        $result = $original->subSeconds(30);

        $this->assertSame('2024-01-01 10:00:00', $result->format(TimeFormat::DATETIME));
        $this->assertSame('2024-01-01 10:00:30', $original->format(TimeFormat::DATETIME)); // immutability
    }

    public function testSubMinutes(): void
    {
        $instant = Instant::parse('2024-01-01 10:45:00');

        $this->assertSame('2024-01-01 10:00:00', $instant->subMinutes(45)->format(TimeFormat::DATETIME));
    }

    public function testSubHours(): void
    {
        $instant = Instant::parse('2024-01-01 16:00:00');

        $this->assertSame('2024-01-01 10:00:00', $instant->subHours(6)->format(TimeFormat::DATETIME));
    }

    public function testSubDays(): void
    {
        $instant = Instant::parse('2024-01-08 10:00:00');

        $this->assertSame('2024-01-01 10:00:00', $instant->subDays(7)->format(TimeFormat::DATETIME));
    }

    public function testSubWeeks(): void
    {
        $instant = Instant::parse('2024-01-15 10:00:00');

        $this->assertSame('2024-01-01 10:00:00', $instant->subWeeks(2)->format(TimeFormat::DATETIME));
    }

    public function testSubMonths(): void
    {
        $instant = Instant::parse('2024-04-15 10:00:00');

        $this->assertSame('2024-01-15 10:00:00', $instant->subMonths(3)->format(TimeFormat::DATETIME));
    }

    public function testSubYears(): void
    {
        $instant = Instant::parse('2026-01-01 10:00:00');

        $this->assertSame('2024-01-01 10:00:00', $instant->subYears(2)->format(TimeFormat::DATETIME));
    }

    public function testAddDateInterval(): void
    {
        $instant = Instant::parse('2024-01-01 10:00:00');
        $interval = new DateInterval('P1DT2H30M');

        $result = $instant->add($interval);

        $this->assertSame('2024-01-02 12:30:00', $result->format(TimeFormat::DATETIME));
    }

    public function testSubDateInterval(): void
    {
        $instant = Instant::parse('2024-01-02 12:30:00');
        $interval = new DateInterval('P1DT2H30M');

        $result = $instant->sub($interval);

        $this->assertSame('2024-01-01 10:00:00', $result->format(TimeFormat::DATETIME));
    }

    public function testModify(): void
    {
        $instant = Instant::parse('2024-01-01 10:00:00');

        $this->assertSame('2024-01-02 10:00:00', $instant->modify('+1 day')->format(TimeFormat::DATETIME));
        $this->assertSame('2024-01-01 08:00:00', $instant->modify('-2 hours')->format(TimeFormat::DATETIME));
    }

    public function testImmutability(): void
    {
        $original = Instant::parse('2024-01-01 10:00:00');
        $modified = $original->addDays(1);

        $this->assertNotSame($original, $modified);
        $this->assertSame('2024-01-01 10:00:00', $original->format(TimeFormat::DATETIME));
        $this->assertSame('2024-01-02 10:00:00', $modified->format(TimeFormat::DATETIME));
    }

    // ========================================
    // Difference Tests
    // ========================================

    public function testDiffReturnsDateInterval(): void
    {
        $instant1 = Instant::parse('2024-01-01 10:00:00');
        $instant2 = Instant::parse('2024-01-03 12:30:45');

        $diff = $instant1->diff($instant2);

        $this->assertInstanceOf(DateInterval::class, $diff);
        $this->assertSame(2, $diff->d);
        $this->assertSame(2, $diff->h);
        $this->assertSame(30, $diff->i);
        $this->assertSame(45, $diff->s);
    }

    public function testDiffAbsolute(): void
    {
        $instant1 = Instant::parse('2024-01-03 12:00:00');
        $instant2 = Instant::parse('2024-01-01 10:00:00');

        $diff = $instant1->diff($instant2, absolute: true);

        $this->assertSame(0, $diff->invert);
    }

    public function testDiffInSeconds(): void
    {
        $instant1 = Instant::parse('2024-01-01 10:00:00');
        $instant2 = Instant::parse('2024-01-01 10:01:30');

        $this->assertSame(-90, $instant1->diffInSeconds($instant2));
        $this->assertSame(90, $instant2->diffInSeconds($instant1));
    }

    public function testDiffInMinutes(): void
    {
        $instant1 = Instant::parse('2024-01-01 10:00:00');
        $instant2 = Instant::parse('2024-01-01 12:30:00');

        $this->assertSame(-150, $instant1->diffInMinutes($instant2));
        $this->assertSame(150, $instant2->diffInMinutes($instant1));
    }

    public function testDiffInHours(): void
    {
        $instant1 = Instant::parse('2024-01-01 10:00:00');
        $instant2 = Instant::parse('2024-01-01 16:00:00');

        $this->assertSame(-6, $instant1->diffInHours($instant2));
        $this->assertSame(6, $instant2->diffInHours($instant1));
    }

    public function testDiffInDays(): void
    {
        $instant1 = Instant::parse('2024-01-01 10:00:00');
        $instant2 = Instant::parse('2024-01-08 10:00:00');

        $this->assertSame(-7, $instant1->diffInDays($instant2));
        $this->assertSame(7, $instant2->diffInDays($instant1));
    }

    // ========================================
    // Boundary Tests
    // ========================================

    public function testStartOfDay(): void
    {
        $instant = Instant::parse('2024-01-15 14:30:45');

        $this->assertSame('2024-01-15 00:00:00', $instant->startOfDay()->format(TimeFormat::DATETIME));
    }

    public function testEndOfDay(): void
    {
        $instant = Instant::parse('2024-01-15 14:30:45');
        $endOfDay = $instant->endOfDay();

        $this->assertSame('2024-01-15', $endOfDay->format(TimeFormat::DATE));
        $this->assertSame('23:59:59', $endOfDay->format(TimeFormat::TIME));
    }

    public function testStartOfWeekOnMonday(): void
    {
        // Monday
        $instant = Instant::parse('2024-01-15 14:30:45'); // Monday

        $this->assertSame('2024-01-15 00:00:00', $instant->startOfWeek()->format(TimeFormat::DATETIME));
    }

    public function testStartOfWeekOnWednesday(): void
    {
        // Wednesday
        $instant = Instant::parse('2024-01-17 14:30:45');

        $this->assertSame('2024-01-15 00:00:00', $instant->startOfWeek()->format(TimeFormat::DATETIME));
    }

    public function testStartOfWeekOnSunday(): void
    {
        // Sunday
        $instant = Instant::parse('2024-01-21 14:30:45');

        $this->assertSame('2024-01-15 00:00:00', $instant->startOfWeek()->format(TimeFormat::DATETIME));
    }

    public function testEndOfWeek(): void
    {
        // Wednesday
        $instant = Instant::parse('2024-01-17 14:30:45');
        $endOfWeek = $instant->endOfWeek();

        $this->assertSame('2024-01-21', $endOfWeek->format(TimeFormat::DATE)); // Sunday
        $this->assertSame('23:59:59', $endOfWeek->format(TimeFormat::TIME));
    }

    public function testStartOfMonth(): void
    {
        $instant = Instant::parse('2024-01-15 14:30:45');

        $this->assertSame('2024-01-01 00:00:00', $instant->startOfMonth()->format(TimeFormat::DATETIME));
    }

    public function testEndOfMonth(): void
    {
        $instant = Instant::parse('2024-01-15 14:30:45');
        $endOfMonth = $instant->endOfMonth();

        $this->assertSame('2024-01-31', $endOfMonth->format(TimeFormat::DATE));
        $this->assertSame('23:59:59', $endOfMonth->format(TimeFormat::TIME));
    }

    public function testEndOfMonthFebruary(): void
    {
        $instant = Instant::parse('2024-02-15 14:30:45');

        $this->assertSame('2024-02-29', $instant->endOfMonth()->format(TimeFormat::DATE)); // leap year
    }

    public function testStartOfYear(): void
    {
        $instant = Instant::parse('2024-06-15 14:30:45');

        $this->assertSame('2024-01-01 00:00:00', $instant->startOfYear()->format(TimeFormat::DATETIME));
    }

    public function testEndOfYear(): void
    {
        $instant = Instant::parse('2024-06-15 14:30:45');
        $endOfYear = $instant->endOfYear();

        $this->assertSame('2024-12-31', $endOfYear->format(TimeFormat::DATE));
        $this->assertSame('23:59:59', $endOfYear->format(TimeFormat::TIME));
    }

    // ========================================
    // Formatting Tests
    // ========================================

    public function testFormatWithTimeFormatEnum(): void
    {
        $instant = Instant::parse('2024-01-15 14:30:45');

        $this->assertSame('2024-01-15', $instant->format(TimeFormat::DATE));
        $this->assertSame('14:30:45', $instant->format(TimeFormat::TIME));
        $this->assertSame('2024-01-15 14:30:45', $instant->format(TimeFormat::DATETIME));
    }

    public function testFormatWithCustomString(): void
    {
        $instant = Instant::parse('2024-01-15 14:30:45');

        $this->assertSame('15/01/2024', $instant->format('d/m/Y'));
        $this->assertSame('January 15, 2024', $instant->format('F d, Y'));
    }

    public function testToDatetime(): void
    {
        $instant = Instant::parse('2024-01-15 14:30:45');
        $datetime = $instant->toDatetime();

        $this->assertInstanceOf(DateTimeImmutable::class, $datetime);
        $this->assertSame('2024-01-15 14:30:45', $datetime->format('Y-m-d H:i:s'));
    }

    public function testToTimestamp(): void
    {
        $instant = Instant::parse('2024-01-15 12:30:00 UTC');

        $this->assertIsInt($instant->toTimestamp());
        $this->assertSame(1705321800, $instant->toTimestamp());
    }

    public function testToDatetimeStringStatic(): void
    {
        $instant = Instant::parse('2024-01-15 14:30:45');

        $this->assertSame('2024-01-15 14:30:45', Instant::toDatetimeString($instant));
    }

    public function testToString(): void
    {
        $instant = Instant::parse('2024-01-15 14:30:45 UTC');

        $this->assertStringContainsString('2024-01-15T14:30:45', (string) $instant);
    }

    // ========================================
    // DateTimeInterface Tests
    // ========================================

    public function testGetTimezone(): void
    {
        $instant = Instant::create(2024, 1, 15, timezone: Timezone::EUROPE_WARSAW);

        $this->assertSame('Europe/Warsaw', $instant->getTimezone()->getName());
    }

    public function testGetOffset(): void
    {
        $instant = Instant::create(2024, 1, 15, timezone: Timezone::UTC);

        $this->assertSame(0, $instant->getOffset());
    }

    public function testGetTimestamp(): void
    {
        $instant = Instant::parse('2024-01-15 12:30:00 UTC');

        $this->assertSame(1705321800, $instant->getTimestamp());
    }

    public function testGetMicrosecond(): void
    {
        $instant = Instant::create(2024, 1, 15, 10, 0, 0, 123456);

        $this->assertSame(123456, $instant->getMicrosecond());
    }

    public function testToDatetimeReturnsDateTimeImmutable(): void
    {
        $instant = Instant::now();
        $datetime = $instant->toDatetime();

        $this->assertInstanceOf(DateTimeImmutable::class, $datetime);
        $this->assertInstanceOf(DateTimeInterface::class, $datetime);
    }

    // ========================================
    // ORM Integration Tests
    // ========================================

    public function testCastableFromAndToDatetimeString(): void
    {
        // Simulate ORM casting flow
        $dbValue = '2024-01-15 10:30:00';

        // Cast from database string to Instant
        $instant = Instant::from($dbValue);
        $this->assertInstanceOf(Instant::class, $instant);

        // Cast back to database string
        $castBack = Instant::toDatetimeString($instant);
        $this->assertSame($dbValue, $castBack);
    }

    public function testCanBeUsedAsDateTimeInterfaceViaToDatetime(): void
    {
        $instant = Instant::parse('2024-01-15 10:30:00');

        // Use toDatetime() to pass to methods expecting DateTimeInterface
        $formatted = $this->formatDate($instant->toDatetime());

        $this->assertSame('2024-01-15 10:30:00', $formatted);
    }

    private function formatDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    // ========================================
    // ClockFactory Integration Tests
    // ========================================

    public function testClockFactoryInstantReturnsCurrentTime(): void
    {
        $instant = ClockFactory::instant();

        $this->assertInstanceOf(Instant::class, $instant);
        $this->assertSame('2024-06-15 12:00:00', $instant->format(TimeFormat::DATETIME));
    }
}
