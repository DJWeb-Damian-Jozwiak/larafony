<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler;

enum CronSchedule: string
{
    case EVERY_MINUTE = '* * * * *';
    case EVERY_FIVE_MINUTES = '*/5 * * * *';
    case EVERY_FIFTEEN_MINUTES = '*/15 * * * *';
    case EVERY_THIRTY_MINUTES = '*/30 * * * *';
    case HOURLY = '0 * * * *';
    case DAILY = '0 0 * * *';
    case MONTHLY = '0 0 1 * *';
    case YEARLY = '0 0 1 1 *';
    case DAILY_AT_NOON = '0 12 * * *';
    case MONDAY = '0 0 * * 1';
    case TUESDAY = '0 0 * * 2';
    case WEDNESDAY = '0 0 * * 3';
    case THURSDAY = '0 0 * * 4';
    case FRIDAY = '0 0 * * 5';
    case SATURDAY = '0 0 * * 6';
    case SUNDAY = '0 0 * * 0';
    case WEEKDAYS = '0 0 * * 1-5';
    case WEEKENDS = '0 0 * * 0,6';

    public function at(int $hour, int $minute = 0): string
    {
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            throw new \InvalidArgumentException('Invalid time');
        }

        $cronParts = explode(' ', $this->value);
        $cronParts[0] = (string)$minute;
        $cronParts[1] = (string)$hour;
        return implode(' ', $cronParts);
    }

    public static function everyNMinutes(int $minutes): string
    {
        if ($minutes < 1 || $minutes > 59) {
            throw new \InvalidArgumentException('Minutes must be between 1 and 59');
        }

        return sprintf('*/%d * * * *', $minutes);
    }

}
