<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Clauses;

use Larafony\Framework\Database\Base\Query\Enums\JoinType;

/**
 * JOIN clause definition - no bindings needed
 * Simpler interface than WhereClause
 */
abstract class JoinClause
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $conditions = [];

    public function __construct(
        public readonly JoinType $type,
        public readonly string $table
    ) {
    }

    public function on(
        string $first,
        string $operator,
        string $second,
        string $boolean = 'and',
    ): static {
        $this->conditions[] = compact('first', 'operator', 'second', 'boolean');
        return $this;
    }

    public function orOn(string $first, string $operator, string $second): static
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Build SQL for this JOIN clause
     */
    abstract public function getSqlDefinition(): string;
}
