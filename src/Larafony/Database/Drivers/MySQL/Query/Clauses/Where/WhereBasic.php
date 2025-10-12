<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where;

use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;
use Larafony\Framework\Database\Base\Query\Enums\LogicalOperator;

/**
 * Basic WHERE condition: column operator value
 * Example: WHERE name = 'John'
 */
class WhereBasic extends WhereClause
{
    public function __construct(
        public readonly string $column,
        public readonly string $operator,
        public readonly mixed $value,
        public readonly LogicalOperator $boolean = LogicalOperator::AND
    ) {
    }

    public function getSqlDefinition(): string
    {
        return "{$this->boolean->value} {$this->column} {$this->operator} ?";
    }

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return [$this->value];
    }
}
