<?php

declare(strict_types=1);

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Container\Container;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Uri\Rfc3986\Uri;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new Container();
new ErrorHandlerServiceProvider()->register($container)->boot($container);

$path = new Uri($_SERVER['REQUEST_URI'])->getPath();

match ($path) {
    '/' => handleHome(),
    '/error' => handleError(),
    '/exception' => handleException(),
    '/fatal' => handleFatal(),
    default => handleNotFound(),
};

function handleHome(): void
{
    echo '<h1>Larafony Framework Demo</h1>';
    echo '<p>Error Handler is active. Try these endpoints:</p>';
    echo '<p>Now is ' . ClockFactory::timezone(Timezone::EUROPE_WARSAW)
        ->format(TimeFormat::DATETIME) . '</p>';
    echo '<ul>';
    echo '<li><a href="/error">Trigger E_WARNING</a></li>';
    echo '<li><a href="/exception">Trigger Exception</a></li>';
    echo '<li><a href="/fatal">Trigger Fatal Error</a></li>';
    echo '</ul>';
}

function handleError(): void
{
    // Trigger a warning
    trigger_error('This is a triggered warning', E_USER_WARNING);
    echo '<p>Warning triggered! Check the error handler output.</p>';
}

function handleException(): void
{
    throw new RuntimeException('This is a test exception');
}

function handleFatal(): void
{
    // Call undefined function to trigger fatal error
    undefinedFunction();
}

function handleNotFound(): void
{
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    echo '<p><a href="/">Go back home</a></p>';
}
