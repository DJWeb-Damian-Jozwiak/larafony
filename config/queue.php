<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default queue driver that will be used by the
    | framework. Supported drivers: "database", "redis"
    |
    */
    'default' => Env('QUEUE_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each queue driver
    | that is used by your application.
    |
    */
    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'default',
            'retry_after' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | will be able to control which table and connection are used to store
    | the jobs that have failed.
    |
    */
    'failed' => [
        'driver' => 'database',
        'table' => 'failed_jobs',
    ],
];
