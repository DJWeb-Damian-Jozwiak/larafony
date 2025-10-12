<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Contracts;

/**
 * Contract for query clauses that have bindings (WHERE clauses)
 * Similar to Column in Schema - knows how to build itself to SQL
 *
 * Note: JOIN, ORDER BY, LIMIT don't need bindings, so they use
 * simpler abstract classes without this contract
 */
interface ClauseContract
{
    /**
     * Get SQL definition for this clause
     */
    public function getSqlDefinition(): string;

    /**
     * Get bindings for prepared statements
     *
     * @return array<int, mixed>
     */
    public function getBindings(): array;
}
