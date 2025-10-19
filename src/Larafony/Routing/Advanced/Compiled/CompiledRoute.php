<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Compiled;

readonly class CompiledRoute
{
    /**
     * @param string $regex The compiled regex pattern
     * @param array<int, string> $variables Parameter names in order
     * @param array<string, string> $patterns Parameter patterns
     */
    public function __construct(
        public string $regex,
        public array $variables,
        public array $patterns,
    ) {
    }

    /**
     * Match a path against this compiled route
     *
     * @return array<string, string>|null Parameters if matched, null otherwise
     */
    public function match(string $path): ?array
    {
        if (! preg_match($this->regex, $path, $matches)) {
            return null;
        }

        $parameters = [];
        foreach ($this->variables as $variable) {
            // Named groups in regex will be in $matches
            if (isset($matches[$variable])) {
                $parameters[$variable] = $matches[$variable];
            }
        }

        return $parameters;
    }
}
