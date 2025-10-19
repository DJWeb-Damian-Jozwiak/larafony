<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Decorators;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;

class RouteMiddleware
{
    /**
     * @var array<int, string>
     */
    public private(set) array $middlewareBefore = [];
    /**
     * @var array<int, string>
     */
    public private(set) array $middlewareAfter = [];
    /**
     * @var array<int, string>
     */
    public private(set) array $withoutMiddleware = [];

    public function withMiddlewareBefore(string $middleware): static
    {
        $this->verifyMiddleware($middleware);
        $this->middlewareBefore [] = $middleware;
        return $this;
    }

    public function withMiddlewareAfter(string $middleware): static
    {
        $this->verifyMiddleware($middleware);
        $this->middlewareAfter [] = $middleware;
        return $this;
    }

    public function withoutMiddleware(string $middleware): static
    {
        $this->verifyMiddleware($middleware);
        $this->withoutMiddleware [] = $middleware;
        return $this;
    }

    private function verifyMiddleware(string $middleware): void
    {
        if (! is_subclass_of($middleware, MiddlewareInterface::class)) {
            throw new InvalidArgumentException("{$middleware} must implement MiddlewareInterface");
        }
    }
}