<?php

declare(strict_types=1);

use App\Http\Controllers\DemoController;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Psr\Http\Message\ServerRequestInterface;

// Test PHP 8.5 first-class callable in const expression

/**
 * @var \Larafony\Framework\Web\Application $app
 */
$app = require_once __DIR__ . '/../bootstrap/web_app.php';
$app->withServiceProviders([
    ErrorHandlerServiceProvider::class,
    HttpServiceProvider::class,
]);
// Get PSR-7 request from container
$request = $app->get(ServerRequestInterface::class);

// Simple routing based on path (until routing chapter)
$path = $request->getUri()->getPath();

$controller = $app->get(DemoController::class);

$response = match ($path) {
    '/' => $controller->home($request),
    '/info' => $controller->info($request),
    '/error' => $controller->handleError($request),
    '/exception' => $controller->handleException($request),
    '/fatal' => $controller->handleFatal($request),
    default => $controller->handleNotFound($request),
};

// Emit PSR-7 response
$app->emit($response);
