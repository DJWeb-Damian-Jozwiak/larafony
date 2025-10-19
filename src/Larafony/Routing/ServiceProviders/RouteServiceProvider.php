<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\ServiceProviders;

use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Routing\Advanced\AttributeRouteLoader;
use Larafony\Framework\Routing\Advanced\AttributeRouteScanner;
use Larafony\Framework\Routing\Advanced\RouteMatcher;
use Larafony\Framework\Routing\Advanced\Router;
use Larafony\Framework\Routing\Basic\Factories\ArrayHandlerFactory;
use Larafony\Framework\Routing\Basic\Factories\StringHandlerFactory;
use Larafony\Framework\Routing\Basic\RouteCollection;
use Psr\Http\Server\RequestHandlerInterface;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * @var array<int|string, class-string> $providers
     */
    public array $providers {
        get => [
            RequestHandlerInterface::class => Router::class,
            Router::class => Router::class,
            ArrayHandlerFactory::class => ArrayHandlerFactory::class,
            StringHandlerFactory::class => StringHandlerFactory::class,
            AttributeRouteScanner::class => AttributeRouteScanner::class,
            AttributeRouteLoader::class => AttributeRouteLoader::class,
            RouteMatcher::class => RouteMatcher::class,
        ];
    }

    public function boot(\Larafony\Framework\Container\Contracts\ContainerContract $container): void
    {
        // Register RouteCollection with Advanced\RouteMatcher
        $collection = new RouteCollection(
            $container,
            $container->get(RouteMatcher::class)
        );
        $container->set(RouteCollection::class, $collection);
    }
}
