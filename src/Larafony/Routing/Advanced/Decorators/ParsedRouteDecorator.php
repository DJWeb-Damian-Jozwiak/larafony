<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Decorators;

use Larafony\Framework\Routing\Advanced\RouteParameter;

class ParsedRouteDecorator
{
    /**
     * @var array<string, RouteParameter>
     */
    public private(set) array $definitions = [];

    public function __construct(private readonly string $path)
    {
        $this->parseParameters();
    }

    private function parseParameters(): void
    {
        preg_match_all('/<([^>]+)>/', $this->path, $matches);

        foreach ($matches[1] as $param) {
            $parts = explode(':', $param);
            $name = $parts[0];
            $pattern = $parts[1] ?? '[^/]+';

            $this->definitions[$name] = new RouteParameter(
                name: $name,
                pattern: $pattern,
            );
        }
    }
}
