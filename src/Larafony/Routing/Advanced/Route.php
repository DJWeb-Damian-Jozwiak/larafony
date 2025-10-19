<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Routing\Advanced\Decorators\ParsedRouteDecorator;
use Larafony\Framework\Routing\Advanced\Decorators\RouteMiddleware;
use Larafony\Framework\Routing\Basic\Route as BasicRoute;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;

class Route extends BasicRoute
{
    public private(set) ParsedRouteDecorator $parsedParams;

    /**
     * @var array<int|string, RouteBinding>
     */
    public private(set) array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    public private(set) array $parameters = [];

    private readonly RouteMiddleware $middleware;

    public function __construct(
        string $path,
        HttpMethod $method,
        \Closure|array|string $handlerDefinition,
        RouteHandlerFactory $factory,
        ?string $name = null,
    ) {
        parent::__construct($path, $method, $handlerDefinition, $factory, $name);
        $this->parsedParams = new ParsedRouteDecorator($this->path);
    }

    public function bind(string $parameter, string $model, string $findMethod = 'findForRoute'): self
    {
        $this->bindings[$parameter] = new RouteBinding(modelClass: $model, findMethod: $findMethod);

        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return $this
     */
    public function withParameters(array $parameters): static
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function withMiddlewareBefore(string $middleware): static
    {
        $this->middleware->withMiddlewareBefore($middleware);
        return $this;
    }

    public function withMiddlewareAfter(string $middleware): static
    {
        $this->middleware->withMiddlewareAfter($middleware);
        return $this;
    }

    public function withoutMiddleware(string $middleware): static
    {
        $this->middleware->withoutMiddleware($middleware);
        return $this;
    }
}