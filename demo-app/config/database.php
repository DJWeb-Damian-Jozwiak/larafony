<?php

declare(strict_types=1);

use Larafony\Framework\Config\Environment\EnvReader;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    */

    'default' => EnvReader::read('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'path' => 'database/migrations',
    ],

    'connections' => [

        'mysql' => [
            'driver' => 'mysql',
            'host' => EnvReader::read('DB_HOST', '127.0.0.1'),
            'port' => (int) EnvReader::read('DB_PORT', '3306'),
            'database' => EnvReader::read('DB_DATABASE', 'larafony'),
            'username' => EnvReader::read('DB_USERNAME', 'root'),
            'password' => EnvReader::read('DB_PASSWORD', ''),
            'charset' => EnvReader::read('DB_CHARSET', 'utf8mb4'),
            'collation' => EnvReader::read('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => EnvReader::read('DB_PREFIX', ''),
            'strict' => (bool) EnvReader::read('DB_STRICT_MODE', 'true'),
            'engine' => EnvReader::read('DB_ENGINE', 'InnoDB'),
        ],

    ],

];
