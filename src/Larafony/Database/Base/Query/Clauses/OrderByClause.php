<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Clauses;

use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;

/**
 * ORDER BY clause definition - no bindings needed
 * Simpler interface than WhereClause
 */
abstract class OrderByClause
{
    public function __construct(
        public readonly string $column,
        public readonly OrderDirection $direction = OrderDirection::ASC
    ) {
    }

    /**
     * Build SQL for this ORDER BY clause
     */
    abstract public function getSqlDefinition(): string;
}
