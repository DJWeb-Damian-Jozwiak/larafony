<?php

declare(strict_types=1);

use App\Http\Controllers\DemoController;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Routing\Basic\Router;
use Larafony\Framework\Routing\ServiceProviders\RouteServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';
$app = \Larafony\Framework\Web\Application::instance();
$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,
    HttpServiceProvider::class,
    RouteServiceProvider::class,
]);
$app->withRoutes(static function (Router $router): void {
    $router->addRouteByParams('GET', '/', [DemoController::class, 'home']);
    $router->addRouteByParams('GET', '/info', [DemoController::class, 'info']);
    $router->addRouteByParams('GET', '/error', [DemoController::class, 'handleError']);
    $router->addRouteByParams('GET', '/exception', [DemoController::class, 'handleException']);
    $router->addRouteByParams('GET', '/fatal', [DemoController::class, 'handleFatal']);
});
//$app->run();
return $app;
