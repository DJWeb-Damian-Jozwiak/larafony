<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where;

use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;
use Larafony\Framework\Database\Base\Query\Enums\LogicalOperator;

/**
 * WHERE NULL condition
 * Example: WHERE deleted_at IS NULL
 */
class WhereNull extends WhereClause
{
    public function __construct(
        public readonly string $column,
        public readonly LogicalOperator $boolean = LogicalOperator::AND,
        public readonly bool $not = false
    ) {
    }

    public function getSqlDefinition(): string
    {
        $operator = $this->not ? 'IS NOT NULL' : 'IS NULL';
        return "{$this->boolean->value} {$this->column} {$operator}";
    }

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return [];
    }
}
