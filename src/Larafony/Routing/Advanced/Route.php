<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Routing\Advanced\Compiled\CompiledRoute;
use Larafony\Framework\Routing\Advanced\Decorators\ParsedRouteDecorator;
use Larafony\Framework\Routing\Advanced\Decorators\RouteMiddleware;
use Larafony\Framework\Routing\Basic\Route as BasicRoute;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public private(set) ?CompiledRoute $compiled = null;

    public string $action {
        get {
            if (is_string($this->handlerDefinition)) {
                return $this->handlerDefinition;
            }

            if (is_array($this->handlerDefinition)) {
                return implode('@', $this->handlerDefinition);
            }

            return 'Closure';
        }
    }

    private readonly RouteMiddleware $middleware;

    public function __construct(
        string $path,
        HttpMethod $method,
        private \Closure|array|string $handlerDefinition,
        RouteHandlerFactory $factory,
        ?string $name = null,
    ) {
        parent::__construct($path, $method, $handlerDefinition, $factory, $name);
        $this->parsedParams = new ParsedRouteDecorator($this->path);
        $this->middleware = new RouteMiddleware();
    }

    public function bind(string $parameter, string $model, string $findMethod = 'findForRoute'): self
    {
        // Check if this parameter name matches a route parameter
        // and if the type is a class (potential model)
        if (! isset($this->parsedParams->definitions[$parameter])) {
            return $this;
        }
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

    public function getMiddleware(): RouteMiddleware
    {
        return $this->middleware;
    }

    public function compile(CompiledRoute $compiled): static
    {
        $this->compiled = $compiled;
        return $this;
    }

    /**
     * Handle the request by adding route parameters to request attributes
     * before delegating to the handler
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Add all route parameters to request attributes
        foreach ($this->parameters as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return parent::handle($request);
    }
}
