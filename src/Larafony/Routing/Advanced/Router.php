<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Events\Routing\RouteMatched;
use Larafony\Framework\Routing\Basic\RouteCollection;
use Larafony\Framework\Routing\Basic\Router as BaseRouter;
use Larafony\Framework\Routing\Exceptions\RouteNotFoundError;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router extends BaseRouter
{
    /**
     * @var array<int, RouteGroup>
     */
    public private(set) array $groups = [];

    private ModelBinder $modelBinder;
    private ?UrlGenerator $urlGenerator = null;

    public function __construct(
        RouteCollection $routes,
        ContainerContract $container,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly string $baseUrl = '',
    ) {
        parent::__construct($routes, $container);
        $this->modelBinder = new ModelBinder($container);
    }

    /**
     * Generate URL for a named route.
     *
     * @param string $name Route name
     * @param array<string, mixed> $params Route parameters
     * @param bool $absolute Generate absolute URL
     *
     * @return string Generated URL
     */
    public function generate(string $name, array $params = [], bool $absolute = false): string
    {
        return $this->getUrlGenerator()->route($name, $params, $absolute);
    }

    /**
     * Get the URL generator instance.
     */
    public function getUrlGenerator(): UrlGenerator
    {
        return $this->urlGenerator ??= new UrlGenerator($this->routes, $this->baseUrl);
    }
    /**
     * Handle the request by finding and executing the matched route
     *
     * @param ServerRequestInterface $request The incoming request
     *
     * @return ResponseInterface The response from the handler
     *
     * @throws RouteNotFoundError If no matching route is found
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $this->routes->findRoute($request);

        // If it's an Advanced\Route, resolve model bindings
        if ($route instanceof Route && $route->bindings) {
            $boundParameters = $this->modelBinder->resolveBindings($route);
            $route = $route->withParameters($boundParameters);
        }

        // Dispatch RouteMatched event
        if ($this->eventDispatcher && $route instanceof Route) {
            $this->eventDispatcher->dispatch(
                new RouteMatched($route, $route->parameters)
            );
        }

        return $route->handle($request);
    }

    public function loadAttributeRoutes(string $path): self
    {
        $loader = $this->container->get(AttributeRouteLoader::class);
        $routes = $loader->loadFromDirectory($path);

        foreach ($routes as $route) {
            $this->addRoute($route);
        }

        return $this;
    }

    /**
     * @param array<int, string> $middlewareBefore
     * @param array<int, string> $middlewareAfter
     */
    public function group(
        string $prefix,
        callable $callback,
        array $middlewareBefore = [],
        array $middlewareAfter = [],
    ): self {
        $group = new RouteGroup($prefix, $middlewareBefore, $middlewareAfter);
        $callback($group);

        foreach ($group->routes as $route) {
            $this->addRoute($route);
        }

        $this->groups[] = $group;

        return $this;
    }
}
