<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where;

use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;

/**
 * WHERE NULL condition
 * Example: WHERE deleted_at IS NULL
 */
class WhereNull extends WhereClause
{
    public function __construct(
        public readonly string $column,
        public readonly string $boolean = 'and',
        public readonly bool $not = false
    ) {
    }

    public function getSqlDefinition(): string
    {
        $operator = $this->not ? 'IS NOT NULL' : 'IS NULL';
        return "{$this->boolean} {$this->column} {$operator}";
    }

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return [];
    }
}
