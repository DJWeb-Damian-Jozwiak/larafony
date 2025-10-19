<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Routing\Basic\RouteCollection;
use Larafony\Framework\Routing\Basic\Router as BaseRouter;
use Larafony\Framework\Routing\Exceptions\RouteNotFoundError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Router extends BaseRouter
{
    /**
     * @var array<int, RouteGroup>
     */
    public private(set) array $groups = [];

    private ModelBinder $modelBinder;

    public function __construct(RouteCollection $routes, ContainerContract $container)
    {
        parent::__construct($routes, $container);
        $this->modelBinder = new ModelBinder($container);
    }
    /**
     * Dispatch the request to the appropriate handler.
     *
     * @param ServerRequestInterface $request The incoming request
     *
     * @return ResponseInterface The response from the handler
     *
     * @throws RouteNotFoundError If no matching route is found
     */
    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $response = $this->handle($request);
        return $next->handle($request->withAttribute('route_response', $response));
    }
}