<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Clauses;

/**
 * MySQL LIMIT/OFFSET clause implementation
 */
class LimitClause extends \Larafony\Framework\Database\Base\Query\Clauses\LimitClause
{
    public function getSqlDefinition(): string
    {
        $sql = '';

        if ($this->limit !== null) {
            $sql .= "LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return trim($sql);
    }
}
