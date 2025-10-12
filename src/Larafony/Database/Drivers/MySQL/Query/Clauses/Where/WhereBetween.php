<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where;

use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;
use Larafony\Framework\Database\Base\Query\Enums\LogicalOperator;

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
        public readonly LogicalOperator $boolean = LogicalOperator::AND,
        public readonly bool $not = false
    ) {
    }

    public function getSqlDefinition(): string
    {
        $operator = $this->not ? 'NOT BETWEEN' : 'BETWEEN';
        return "{$this->boolean->value} {$this->column} {$operator} ? AND ?";
    }

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return [$this->values[0], $this->values[1]];
    }
}
