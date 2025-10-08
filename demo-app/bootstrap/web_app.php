<?php

declare(strict_types=1);

use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';
$app = \Larafony\Framework\Web\Application::instance();
$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,
    HttpServiceProvider::class,
]);
return $app;
