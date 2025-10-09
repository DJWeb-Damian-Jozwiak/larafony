<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Routing\Exceptions\RouteNotFoundError;
use Psr\Http\Message\ServerRequestInterface;

class RouteCollection
{
    /** @var array<int, Route> */
    public private(set) array $routes = [];

    public function __construct(private readonly ContainerContract $container)
    {
    }

    public function addRoute(Route $route): void
    {
        $this->routes[] = $route;
        //important to set the route collection in the container!!
        $this->container->set(RouteCollection::class, $this);
    }

    /**
     * Find a route that matches the given request.
     *
     * @param ServerRequestInterface $request The request to match against
     *
     * @return Route The matching route, or null if no match is found
     */
    public function findRoute(ServerRequestInterface $request): Route
    {
        $matcher = new RouteMatcher();
        return array_find(
            $this->routes,
            static fn (Route $route) => $matcher->matches($request, $route)
        ) ?? throw new RouteNotFoundError($request);
    }
}
