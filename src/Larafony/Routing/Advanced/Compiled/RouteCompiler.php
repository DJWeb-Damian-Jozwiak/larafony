<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Compiled;

use Larafony\Framework\Routing\Advanced\Route;

class RouteCompiler
{
    /**
     * Compile a route into an optimized regex pattern
     */
    public function compile(Route $route): CompiledRoute
    {
        $path = $route->path;
        $variables = [];
        $patterns = [];

        // Extract variables and their patterns from the path
        preg_match_all('/<(\w+)(?::([^>]+))?>/', $path, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $variable = $match[1];
            $pattern = $match[2] ?? '[\d\p{L}-]+';

            $variables[] = $variable;
            $patterns[$variable] = $pattern;
        }

        // Build the compiled regex
        $regex = $this->buildRegex($path, $patterns);

        return new CompiledRoute($regex, $variables, $patterns);
    }

    /**
     * @param array<string, string> $patterns
     */
    private function buildRegex(string $path, array $patterns): string
    {
        // Escape forward slashes for regex
        $regex = str_replace('/', '\/', $path);

        // Replace parameter placeholders with named capture groups
        $regex = preg_replace_callback(
            '/<(\w+)(?::([^>]+))?>/',
            static function ($matches) use ($patterns) {
                $variable = $matches[1];
                $pattern = $patterns[$variable];

                return '(?<' . $variable . '>' . $pattern . ')';
            },
            $regex
        );

        // Add anchors for exact matching
        return '#^' . $regex . '$#u';
    }
}
