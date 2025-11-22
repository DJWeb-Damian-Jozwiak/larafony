<?php

declare(strict_types=1);

use Larafony\Framework\Storage\Session\Handlers\FileSessionHandler;
use Larafony\Framework\Web\Application;

$app = Application::instance();

return [
    /*
    |--------------------------------------------------------------------------
    | Session Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the session handler used by the application.
    | Supported: FileSessionHandler, DatabaseSessionHandler
    |
    */
    'handler' => FileSessionHandler::class,

    /*
    |--------------------------------------------------------------------------
    | Session File Storage Path
    |--------------------------------------------------------------------------
    |
    | When using the file session handler, this path determines where
    | session files will be stored. Only used with FileSessionHandler.
    |
    */
    'path' => $app->storage_path . '/framework/sessions',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the session cookie settings for your application.
    | All session data is encrypted using XChaCha20-Poly1305 AEAD cipher.
    |
    */
    'cookie_params' => [
        'lifetime' => 7200, // 2 hours (in seconds)
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax', // Lax, Strict, or None
    ],
];
