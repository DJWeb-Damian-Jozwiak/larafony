<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\Environment\Dto;

/**
 * Wynik parsowania całego pliku .env
 */
final class ParserResult
{
    /**
     * @var array<string, EnvironmentVariable>
     */
    public private(set) array $variables;
    /**
     * @param array<int, EnvironmentVariable> $variables
     * @param array<ParsedLine> $lines
     */
    public function __construct(
        array $variables,
        public readonly array $lines = [],
        public readonly int $totalLines = 0,
    ) {
        $this->variables = [];
        foreach ($variables as $variable) {
            $this->variables[$variable->key] = $variable;
        }
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (EnvironmentVariable $var) => $var->value,
            $this->variables
        );
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->variables[$key]->value ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->variables[$key]);
    }

    public function count(): int
    {
        return count($this->variables);
    }
}
