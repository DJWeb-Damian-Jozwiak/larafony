<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler;

use Larafony\Framework\Web\Application;
use Larafony\Framework\Scheduler\Contracts\JobContract;
use Larafony\Framework\Scheduler\Contracts\ScheduleContract;

class Schedule implements ScheduleContract
{
    /**
     * @var array<int, ScheduledEvent>
     */
    private array $events = [];
public function cron(CronSchedule|string $expression, JobContract $job): void
    {
        $cronExpression = match(true) {
            $expression instanceof CronSchedule => $expression->value,
            is_string($expression) => $expression,

        };
$this->events[] = new ScheduledEvent($cronExpression, $job);
}

    /**
     * @param array<string, string> $config
     */
    public function addFromConfig(array $config): void
    {
        foreach ($config as $jobClass => $schedule) {
            if (! class_exists($jobClass)) {
                throw new \InvalidArgumentException("Job class {$jobClass} does not exist");

            }

            if (! is_subclass_of($jobClass, JobContract::class)) {
                throw new \InvalidArgumentException("Job class {$jobClass} must implement JobContract");

            }

            //auto bind default job constructor params
            $job = Application::instance()->get($jobClass);
$this->cron($schedule, $job);

        }
    }

    /**
     * @return array<int, ScheduledEvent>
     */
    public function dueEvents(): array
    {
        return array_filter($this->events, static fn (ScheduledEvent $event) => $event->isDue());
    }

    public function everyNMinutes(int $minutes, JobContract $job): void
    {
        $this->cron(CronSchedule::everyNMinutes($minutes), $job);
    }

}
