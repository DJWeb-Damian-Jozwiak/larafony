<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where;

use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;

/**
 * WHERE BETWEEN condition
 * Example: WHERE age BETWEEN 18 AND 65
 */
class WhereBetween extends WhereClause
{
    /**
     * @param array<int, mixed> $values
     */
    public function __construct(
        public readonly string $column,
        public readonly array $values,
        public readonly string $boolean = 'and',
        public readonly bool $not = false
    ) {
    }

    public function getSqlDefinition(): string
    {
        $operator = $this->not ? 'NOT BETWEEN' : 'BETWEEN';
        return "{$this->boolean} {$this->column} {$operator} ? AND ?";
    }

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return [$this->values[0], $this->values[1]];
    }
}
