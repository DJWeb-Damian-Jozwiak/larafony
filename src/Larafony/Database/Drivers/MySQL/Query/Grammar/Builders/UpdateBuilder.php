<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Builders;

use Larafony\Framework\Database\Base\Query\QueryDefinition;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components\WhereBuilder;

/**
 * UPDATE query builder - compiles UPDATE queries
 * Grammar partial for UPDATE statements
 */
class UpdateBuilder
{
    public function build(QueryDefinition $query): string
    {
        $sql = [];

        // UPDATE table
        $sql[] = "UPDATE {$query->table}";

        // SET column = ?
        $setParts = [];
        foreach ($query->values as $column => $value) {
            $setParts[] = "{$column} = ?";
        }
        $sql[] = 'SET ' . implode(', ', $setParts);

        $sql[] = new WhereBuilder()->build($query->wheres);

        return implode(' ', array_filter($sql));
    }
}
