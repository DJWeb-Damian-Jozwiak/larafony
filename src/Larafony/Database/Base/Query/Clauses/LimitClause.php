<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Clauses;

/**
 * LIMIT/OFFSET clause definition - no bindings needed
 * Simpler interface than WhereClause
 */
abstract class LimitClause
{
    public function __construct(
        public readonly ?int $limit = null,
        public readonly ?int $offset = null
    ) {
    }

    /**
     * Build SQL for this LIMIT/OFFSET clause
     */
    abstract public function getSqlDefinition(): string;
}
