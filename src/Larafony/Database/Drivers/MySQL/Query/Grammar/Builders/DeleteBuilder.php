<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Builders;

use Larafony\Framework\Database\Base\Query\QueryDefinition;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components\WhereBuilder;

/**
 * DELETE query builder - compiles DELETE queries
 * Grammar partial for DELETE statements
 */
class DeleteBuilder
{
    public function build(QueryDefinition $query): string
    {
        $sql = [];

        // DELETE FROM table
        $sql[] = "DELETE FROM {$query->table}";

        // WHERE
        $sql[] = new WhereBuilder()->build($query->wheres);

        return implode(' ', array_filter($sql));
    }
}
