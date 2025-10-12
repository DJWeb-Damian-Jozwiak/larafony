<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Builders;

use Larafony\Framework\Database\Base\Query\QueryDefinition;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components\JoinBuilder;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components\LimitBuilder;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components\OrderByBuilder;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components\WhereBuilder;

/**
 * SELECT query builder - compiles SELECT queries
 * Grammar partial for SELECT statements
 */
class SelectBuilder
{
    public function build(QueryDefinition $query): string
    {
        $sql = [];

        // SELECT columns
        $sql[] = 'SELECT ' . implode(', ', $query->columns);

        // FROM table
        $sql[] = "FROM {$query->table}";
        $sql[] = new JoinBuilder()->build($query->joins);
        $sql[] = new WhereBuilder()->build($query->wheres);
        $sql[] = new OrderByBuilder()->build($query->orders);
        $sql[] = new LimitBuilder()->build($query->limit);

        return implode(' ', array_filter($sql));
    }
}
