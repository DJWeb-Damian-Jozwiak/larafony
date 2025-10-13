<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where;

use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;

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
        public readonly string $boolean = 'and'
    ) {
    }

    public function getSqlDefinition(): string
    {
        return "{$this->boolean} {$this->column} {$this->operator} ?";
    }

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return [$this->value];
    }
}
