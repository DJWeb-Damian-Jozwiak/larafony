<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

class RouteGroup
{
    /**
     * @var array<int, Route>
     */
    public private(set) array $routes = [];

    public readonly string $prefix;

    /**
     * @param string $prefix
     * @param array<int, string> $middlewareBefore
     * @param array<int, string> $middlewareAfter
     */
    public function __construct(
        string $prefix = '',
        public readonly array $middlewareBefore = [],
        public readonly array $middlewareAfter = []
    ) {
        $this->prefix = $this->normalizePath($prefix);
    }

    /**
     * @param string $prefix
     * @param callable $callback
     * @param array<int, string> $middleware
     *
     * @return void
     */
    public function group(
        string $prefix,
        callable $callback,
        array $middleware = []
    ): void {
        $fullPrefix = $this->prefix . '/' . ltrim($prefix, '/');

        $group = new RouteGroup(
            prefix: $fullPrefix,
            middlewareBefore: [...$this->middlewareBefore, ...$middleware]
        );

        $callback($group);

        foreach ($group->routes as $route) {
            $this->routes[] = $route;
        }
    }

    public function addRoute(Route $route): void
    {
        $route->path = $this->prefix . '/' . ltrim($route->path, '/');
        $before = $this->middlewareBefore;
        $after = $this->middlewareAfter;
        array_walk($before, static fn (string $middleware) => $route->withMiddlewareBefore($middleware));
        array_walk($after, static fn (string $middleware) => $route->withMiddlewareAfter($middleware));
        $this->routes[] = $route;
    }

    private function normalizePath(string $path): string
    {
        // remove trailing slash
        $path = rtrim($path, '/');

        // swa[ multiple slashes na to single one
        $path = preg_replace('#/+#', '/', $path);
        $path ??= '';

        // add leading slash if missing
        if (! str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path;
    }
}
