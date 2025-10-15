<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Contracts;

use Larafony\Framework\Database\Base\Query\Enums\QueryType;
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
    public function compileSql(QueryType $type, QueryDefinition $query): string;

    /**
     * @param array<int, mixed> $bindings
     */
    public function substituteBindingsIntoRawSql(string $sql, array $bindings): string;
}
