<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where;

use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;

/**
 * WHERE LIKE condition
 * Example: WHERE name LIKE '%John%'
 */
class WhereLike extends WhereClause
{
    public function __construct(
        public readonly string $column,
        public readonly string $pattern,
        public readonly string $boolean = 'and'
    ) {
    }

    public function getSqlDefinition(): string
    {
        return "{$this->boolean} {$this->column} LIKE ?";
    }

    /**
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return [$this->pattern];
    }
}
