<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components;

use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;

/**
 * WHERE clause builder - builds WHERE part of SQL
 * Grammar partial component
 */
class WhereBuilder
{
    /**
     * @param array<int, WhereClause> $wheres
     */
    public function build(array $wheres): string
    {
        if (! $wheres) {
            return '';
        }

        $sql = [];
        foreach ($wheres as $i => $where) {
            $clause = $where->getSqlDefinition();

            // First condition doesn't need logical operator prefix
            if ($i === 0) {
                $clause = preg_replace('/^(and|or) /', '', $clause);
            }

            $sql[] = $clause;
        }

        return 'WHERE ' . implode(' ', $sql);
    }
}
