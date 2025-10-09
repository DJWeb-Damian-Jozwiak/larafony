<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Http\Enums\HttpMethod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router implements RequestHandlerInterface
{
    public function __construct(public private(set) RouteCollection $routes, private ContainerContract $container)
    {
    }

    public function addRoute(Route $route): self
    {
        $this->routes->addRoute($route);
        return $this;
    }

    /**
     * @param \Closure|array{class-string, string} $handler
     */
    public function addRouteByParams(
        string $method,
        string $path,
        \Closure|array $handler,
        ?string $name = null
    ): self {
        return $this->addRoute(
            new Route(
                $path,
                HttpMethod::from(strtoupper($method)),
                $handler,
                $this->container->get(RouteHandlerFactory::class),
                $name
            )
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->routes->findRoute($request)->handle($request);
    }
}
