<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where;

use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;

/**
 * WHERE IN condition
 * Example: WHERE id IN (1, 2, 3)
 */
class WhereIn extends WhereClause
{
    /**
     * @param array<int, mixed> $values
     */
    public function __construct(
        public readonly string $column,
        public readonly array $values,
        public readonly string $boolean = 'string',
        public readonly bool $not = false
    ) {
    }

    public function getSqlDefinition(): string
    {
        $placeholders = implode(', ', array_fill(0, count($this->values), '?'));
        $operator = $this->not ? 'NOT IN' : 'IN';
        return "{$this->boolean} {$this->column} {$operator} ({$placeholders})";
    }

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return $this->values;
    }
}
