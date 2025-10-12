<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Contracts;

use Larafony\Framework\Database\Base\Query\QueryDefinition;

/**
 * Grammar contract - delegates to specific builders for each query type
 */
interface GrammarContract
{
    public function compileSelect(QueryDefinition $query): string;

    public function compileInsert(QueryDefinition $query): string;

    public function compileUpdate(QueryDefinition $query): string;

    public function compileDelete(QueryDefinition $query): string;
}
