<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler\Contracts;

use Larafony\Framework\Scheduler\CronSchedule;

interface ScheduleContract
{
    public function cron(CronSchedule|string $expression, JobContract $job): void;
    public function everyNMinutes(int $minutes, JobContract $job): void;
}
