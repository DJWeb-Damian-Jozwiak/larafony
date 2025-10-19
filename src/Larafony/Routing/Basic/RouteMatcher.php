<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Routing\Advanced\Route as AdvancedRoute;
use Psr\Http\Message\ServerRequestInterface;

class RouteMatcher
{
    /**
     * Check if this route matches the given request.
     *
     * @param ServerRequestInterface $request The incoming request
     *
     * @return bool True if the route matches, false otherwise
     */
    public function matches(ServerRequestInterface $request, AdvancedRoute $route): bool
    {
        return $this->matchesPath($request->getUri()->getPath(), $route) &&
            $this->matchesMethod($request->getMethod(), $route);
    }
    /**
     * Check if the given path matches this route's path.
     *
     * @param string $path The path to check
     *
     * @return bool True if the path matches, false otherwise
     */
    protected function matchesPath(string $path, AdvancedRoute $route): bool
    {
        $trimmed = rtrim($route->path, '/');
        $trimmed2 = rtrim($path, '/');
        return $trimmed === $trimmed2;
    }

    /**
     * Check if the given method matches this route's method.
     *
     * @param string $method The HTTP method to check
     *
     * @return bool True if the method matches, false otherwise
     */
    private function matchesMethod(string $method, Route $route): bool
    {
        return $route->method === HttpMethod::from(strtoupper($method));
    }
}
