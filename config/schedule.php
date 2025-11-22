<?php

declare(strict_types=1);

use Larafony\Framework\Scheduler\CronSchedule;

return [
    /*
    |--------------------------------------------------------------------------
    | Scheduled Jobs
    |--------------------------------------------------------------------------
    |
    | Here you may define all of your scheduled jobs. Each job is defined by
    | its fully qualified class name as the key and a cron expression as the
    | value. The job class must implement JobContract.
    |
    | Examples:
    | - CronSchedule::EVERY_MINUTE        -> Every minute
    | - CronSchedule::HOURLY              -> Every hour at :00
    | - CronSchedule::DAILY               -> Every day at 00:00
    | - CronSchedule::DAILY_AT_NOON       -> Every day at 12:00
    | - CronSchedule::WEEKLY              -> Every Sunday at 00:00
    | - CronSchedule::MONTHLY             -> First day of month at 00:00
    | - CronSchedule::WEEKDAYS            -> Monday-Friday at 00:00
    | - CronSchedule::SATURDAY->at(14, 30)-> Every Saturday at 14:30
    | - '*/5 * * * *'                     -> Every 5 minutes (custom cron)
    |
    */

    // Example scheduled jobs (uncomment to use):
    // App\Jobs\SendNewsletterJob::class => CronSchedule::DAILY_AT_NOON,
    // App\Jobs\CleanupTempFilesJob::class => CronSchedule::DAILY,
    // App\Jobs\GenerateReportJob::class => CronSchedule::MONDAY->at(9, 0),
    // App\Jobs\BackupDatabaseJob::class => '0 2 * * *', // Every day at 2 AM
];
