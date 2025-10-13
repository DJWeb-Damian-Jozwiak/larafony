<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where;

use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components\WhereBuilder;

/**
 * Nested WHERE conditions with parentheses
 * Example: WHERE (age > 18 OR verified = true)
 */
class WhereNested extends WhereClause
{
    /**
     * @param array<int, WhereClause> $wheres
     */
    public function __construct(
        public readonly array $wheres,
        public readonly string $boolean = 'and'
    ) {
    }

    public function getSqlDefinition(): string
    {
        $builder = new WhereBuilder();
        $nested = $builder->build($this->wheres);
        // Remove "WHERE " prefix
        $nested = substr($nested, 6);
        return "{$this->boolean} ({$nested})";
    }

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return array_merge(...array_map(static fn ($w) => $w->getBindings(), $this->wheres));
    }
}
