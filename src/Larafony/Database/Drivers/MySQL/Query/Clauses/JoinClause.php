<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses;

/**
 * MySQL JOIN clause implementation
 */
class JoinClause extends \Larafony\Framework\Database\Base\Query\Clauses\JoinClause
{
    public function getSqlDefinition(): string
    {
        $sql = "{$this->type->value} JOIN {$this->table}";

        if ($this->conditions) {
            $on = [];
            foreach ($this->conditions as $i => $cond) {
                $prefix = $i === 0 ? '' : "{$cond['boolean']} ";
                $on[] = "{$prefix}{$cond['first']} {$cond['operator']} {$cond['second']}";
            }
            $sql .= ' ON ' . implode(' ', $on);
        }

        return $sql;
    }
}
