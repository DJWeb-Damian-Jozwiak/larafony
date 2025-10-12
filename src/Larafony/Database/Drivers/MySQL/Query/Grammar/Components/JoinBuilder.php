<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components;

use Larafony\Framework\Database\Base\Query\Clauses\JoinClause;

/**
 * JOIN clause builder - builds JOIN part of SQL
 * Grammar partial component
 */
class JoinBuilder
{
    /**
     * @param array<int, JoinClause> $joins
     */
    public function build(array $joins): string
    {
        $sql = [];
        foreach ($joins as $join) {
            $sql[] = $join->getSqlDefinition();
        }

        return implode(' ', $sql) |> trim(...);
    }
}
