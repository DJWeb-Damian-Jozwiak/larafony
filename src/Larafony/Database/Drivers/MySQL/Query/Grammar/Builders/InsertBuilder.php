<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Builders;

use Larafony\Framework\Database\Base\Query\QueryDefinition;

/**
 * INSERT query builder - compiles INSERT queries
 * Grammar partial for INSERT statements
 */
class InsertBuilder
{
    public function build(QueryDefinition $query): string
    {
        $columns = implode(', ', array_keys($query->values));
        $placeholders = implode(', ', array_fill(0, count($query->values), '?'));

        return "INSERT INTO {$query->table} ({$columns}) VALUES ({$placeholders})";
    }
}
