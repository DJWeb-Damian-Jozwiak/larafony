<?php

declare(strict_types=1);

use App\Http\Controllers\DemoController;
use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Routing\Basic\Router;
use Larafony\Framework\Routing\ServiceProviders\RouteServiceProvider;
use Larafony\Framework\View\ServiceProviders\ViewServiceProvider;
use Larafony\Framework\Web\ServiceProviders\WebServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';
$app = \Larafony\Framework\Web\Application::instance(base_path: dirname(__DIR__));
$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,
    ConfigServiceProvider::class,
    HttpServiceProvider::class,
    RouteServiceProvider::class,
    ViewServiceProvider::class,
    WebServiceProvider::class,
]);
$app->withRoutes(static function (Router $router): void {
    $router->addRouteByParams('GET', '/', [DemoController::class, 'home']);
    $router->addRouteByParams('GET', '/info', [DemoController::class, 'info']);
});
//$app->run();
return $app;
