<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query;

use Larafony\Framework\Database\Base\Query\Contracts\GrammarContract;
use Larafony\Framework\Database\Base\Query\QueryDefinition;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Builders\DeleteBuilder;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Builders\InsertBuilder;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Builders\SelectBuilder;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Builders\UpdateBuilder;

/**
 * MySQL Grammar - facade that delegates to specific builders
 * Similar to Schema Grammar
 */
class Grammar implements GrammarContract
{
    public function compileSelect(QueryDefinition $query): string
    {
        return new SelectBuilder()->build($query);
    }

    public function compileInsert(QueryDefinition $query): string
    {
        return new InsertBuilder()->build($query);
    }

    public function compileUpdate(QueryDefinition $query): string
    {
        return new UpdateBuilder()->build($query);
    }

    public function compileDelete(QueryDefinition $query): string
    {
        return new DeleteBuilder()->build($query);
    }
}
