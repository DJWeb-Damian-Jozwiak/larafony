<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Clauses\Where;

use Larafony\Framework\Database\Base\Query\Contracts\ClauseContract;

/**
 * Base WHERE clause - all WHERE conditions extend this
 * Abstract class - concrete SQL building happens in driver-specific implementations
 */
abstract class WhereClause implements ClauseContract
{
    abstract public function getSqlDefinition(): string;

    /**
     * @return array<int, mixed>
     */
    abstract public function getBindings(): array;
}
