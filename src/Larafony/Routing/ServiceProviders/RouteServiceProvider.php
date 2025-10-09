<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\ServiceProviders;

use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Routing\Basic\Factories\ArrayHandlerFactory;
use Larafony\Framework\Routing\Basic\Factories\StringHandlerFactory;
use Larafony\Framework\Routing\Basic\Router;
use Psr\Http\Server\RequestHandlerInterface;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * @var array<int|string, class-string> $providers
     */
    public array $providers {
        get => [
            RequestHandlerInterface::class => Router::class,
            ArrayHandlerFactory::class => ArrayHandlerFactory::class,
            StringHandlerFactory::class => StringHandlerFactory::class,
        ];
    }
}
