<?php

namespace Larafony\Framework\Tests\Scheduler;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Scheduler\CronSchedule;
use Larafony\Framework\Scheduler\Schedule;
use Larafony\Framework\Scheduler\ScheduledEvent;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Larafony\Framework\Tests\Helpers\TestJob;

class ScheduleTest extends TestCase
{
    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schedule = new Schedule();
    }

    public function testCronWithStringExpression(): void
    {
        $job = new TestJob(
            id: '123',
            title: 'Test Job'
        );

        $this->schedule->cron('* * * * *', $job);

        $events = $this->getPrivateProperty($this->schedule, 'events');
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ScheduledEvent::class, $events[0]);
    }

    public function testCronWithCronScheduleEnum(): void
    {
        $job = new TestJob(
            id: '123',
            title: 'Test Job'
        );

        $this->schedule->cron(CronSchedule::EVERY_MINUTE, $job);

        $events = $this->getPrivateProperty($this->schedule, 'events');
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ScheduledEvent::class, $events[0]);
    }

    public function testAddFromConfig(): void
    {
        $config = [
            TestJob::class => '*/5 * * * *'
        ];

        $this->schedule->addFromConfig($config);

        $events = $this->getPrivateProperty($this->schedule, 'events');
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ScheduledEvent::class, $events[0]);
    }

    public function testAddFromConfigWithInvalidJobClass(): void
    {
        $config = [
            'NonExistentJob' => '* * * * *'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Job class NonExistentJob does not exist');

        $this->schedule->addFromConfig($config);
    }

    public function testAddFromConfigWithInvalidJobInterface(): void
    {
        $config = [
            \stdClass::class => '* * * * *'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Job class stdClass must implement JobContract');

        $this->schedule->addFromConfig($config);
    }

    public function testDueEvents(): void
    {
        $job1 = new TestJob(id: '1', title: 'Job 1');
        $job2 = new TestJob(id: '2', title: 'Job 2');

        ClockFactory::freeze(new \DateTimeImmutable('2024-01-01 00:00:00'));

        $this->schedule->cron('* * * * *', $job1);
        $this->schedule->cron('30 * * * *', $job2);

        $dueEvents = $this->schedule->dueEvents();
        $this->assertCount(1, $dueEvents);
        $this->assertSame($job1, $dueEvents[0]->getJob());
    }

    public function testEveryNMinutes(): void
    {
        $job = new TestJob(
            id: '123',
            title: 'Test Job'
        );

        $this->schedule->everyNMinutes(5, $job);

        $events = $this->getPrivateProperty($this->schedule, 'events');
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ScheduledEvent::class, $events[0]);
    }
    public function testEveryInvalidMinutes(): void
    {
        $job = new TestJob(
            id: '123',
            title: 'Test Job'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->schedule->everyNMinutes(69, $job);
    }

    public function testAt(): void
    {
        $job = new TestJob(
            id: '123',
            title: 'Test Job'
        );

        $this->schedule->cron(CronSchedule::SATURDAY->at(hour: 12), $job);

        $events = $this->getPrivateProperty($this->schedule, 'events');
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ScheduledEvent::class, $events[0]);
    }
    public function testInvalidMinuteThrowsException(): void
    {
        $job = new TestJob(
            id: '123',
            title: 'Job for lovers'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->schedule->cron(CronSchedule::SATURDAY->at(hour: 21, minute: 69), $job);
    }

    public function testExcessiveMinuteValueThrowsException(): void
    {
        $job = new TestJob(
            id: '666',
            title: 'Devil Job'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->schedule->cron(CronSchedule::SUNDAY->at(hour: 0, minute: 666), $job);
    }

    private function getPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        return $property->getValue($object);
    }
}
