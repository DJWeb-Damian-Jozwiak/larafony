<?php

declare(strict_types=1);

use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Console\ServiceProviders\ConsoleServiceProvider;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';
$app = \Larafony\Framework\Console\Application::instance(base_path: dirname(__DIR__));
$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,
    HttpServiceProvider::class,
    ConfigServiceProvider::class,
    ConsoleServiceProvider::class,
]);
return $app;
