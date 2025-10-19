<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

use Larafony\Framework\Routing\Basic\RouteCollection;

readonly class UrlGenerator
{
    public function __construct(
        private RouteCollection $routes,
        private string $baseUrl = '',
    ) {
    }

    /**
     * Generate URL for a named route
     *
     * @param string $name Route name
     * @param array<string, mixed> $parameters Route parameters
     * @param bool $absolute Generate absolute URL
     *
     * @return string Generated URL
     *
     * @throws \RuntimeException If route not found
     */
    public function route(string $name, array $parameters = [], bool $absolute = false): string
    {
        $route = $this->routes->findRouteByName($name);
        $path = $this->buildPath($route->path, $parameters);

        return $absolute ? $this->baseUrl . $path : $path;
    }

    /**
     * Generate absolute URL for a named route
     *
     * @param string $name Route name
     * @param array<string, mixed> $parameters Route parameters
     *
     * @return string Generated absolute URL
     */
    public function routeAbsolute(string $name, array $parameters = []): string
    {
        return $this->route($name, $parameters, true);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function buildPath(string $path, array $parameters): string
    {
        // Replace route parameters like <id> or <id:\d+>
        $result = preg_replace_callback(
            '/<(\w+)(?::([^>]+))?>/',
            static function ($matches) use ($parameters) {
                $paramName = $matches[1];

                if (! isset($parameters[$paramName])) {
                    throw new \InvalidArgumentException(
                        "Missing required parameter '{$paramName}' for route"
                    );
                }

                return (string) $parameters[$paramName];
            },
            $path
        );

        // Add query string for extra parameters
        $usedParams = $this->extractParameterNames($path);
        $extraParams = array_diff_key($parameters, array_flip($usedParams));

        if (count($extraParams) > 0) {
            $result .= '?' . http_build_query($extraParams);
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function extractParameterNames(string $path): array
    {
        preg_match_all('/<(\w+)(?::([^>]+))?>/', $path, $matches);
        return $matches[1];
    }
}
