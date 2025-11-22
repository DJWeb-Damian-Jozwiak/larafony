<?php

namespace Larafony\Framework\Tests\Scheduler;

use DateTime;
use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Scheduler\CronExpression;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CronExpressionTest extends TestCase
{
    #[DataProvider('cronExpressionProvider')]
    public function testIsDue(string $expression, string $currentDateTime, bool $expectedResult): void
    {

        ClockFactory::freeze(new \DateTimeImmutable($currentDateTime));

        $cronExpression = new CronExpression($expression);
        $result = $cronExpression->isDue();

        $this->assertEquals($expectedResult, $result);
    }

    public static function cronExpressionProvider(): array
    {
        return [
            'every minute' => [
                '* * * * *',
                '2024-01-01 12:00',
                true
            ],
            'specific minute' => [
                '30 * * * *',
                '2024-01-01 12:30',
                true
            ],
            'specific minute - not matching' => [
                '30 * * * *',
                '2024-01-01 12:31',
                false
            ],
            'minute range' => [
                '15-45 * * * *',
                '2024-01-01 12:30',
                true
            ],
            'minute range - not matching' => [
                '15-45 * * * *',
                '2024-01-01 12:50',
                false
            ],
            'specific hour' => [
                '0 12 * * *',
                '2024-01-01 12:00',
                true
            ],
            'hour range' => [
                '0 9-17 * * *',
                '2024-01-01 13:00',
                true
            ],
            'every 15 minutes' => [
                '*/15 * * * *',
                '2024-01-01 12:30',
                true
            ],
            'every 15 minutes - not matching' => [
                '*/15 * * * *',
                '2024-01-01 12:37',
                false
            ],
            'specific day of month' => [
                '0 0 15 * *',
                '2024-01-15 00:00',
                true
            ],
            'specific month' => [
                '0 0 * 6 *',
                '2024-06-01 00:00',
                true
            ],
            'specific day of week' => [
                '0 0 * * 1',  // Poniedziałek
                '2024-01-01 00:00',
                true
            ],
            'complex expression' => [
                '*/5 9-17 * * 1-5',  // Co 5 minut w godzinach 9-17 w dni robocze
                '2024-01-01 13:05',
                true
            ],
            'complex expression - not matching hour' => [
                '*/5 9-17 * * 1-5',
                '2024-01-01 08:05',
                false
            ],
            'multiple specific times' => [
                '0,15,30,45 * * * *',
                '2024-01-01 12:15',
                true
            ]
        ];
    }

    public function testInvalidCronExpression(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cron expression');

        new CronExpression('* * * *'); // Za mało części
    }

    public function testInvalidCronExpressionTooManyParts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cron expression');

        new CronExpression('* * * * * *'); // Za dużo części
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        ClockFactory::freeze(null); // Reset mock after each test
    }
}
