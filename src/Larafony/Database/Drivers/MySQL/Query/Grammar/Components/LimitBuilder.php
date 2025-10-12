<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components;

use Larafony\Framework\Database\Base\Query\Clauses\LimitClause;

/**
 * LIMIT/OFFSET clause builder - builds LIMIT part of SQL
 * Grammar partial component
 */
class LimitBuilder
{
    public function build(?LimitClause $limit): string
    {
        return $limit?->getSqlDefinition() ?? '';
    }
}
