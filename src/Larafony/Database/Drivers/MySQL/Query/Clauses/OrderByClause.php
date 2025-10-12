<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses;

/**
 * MySQL ORDER BY clause implementation
 */
class OrderByClause extends \Larafony\Framework\Database\Base\Query\Clauses\OrderByClause
{
    public function getSqlDefinition(): string
    {
        return "`{$this->column}` {$this->direction->value}";
    }
}
