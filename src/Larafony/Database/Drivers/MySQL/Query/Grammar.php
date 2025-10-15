<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Query\Contracts\GrammarContract;
use Larafony\Framework\Database\Base\Query\Enums\QueryType;
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
    public function __construct(private readonly ?ConnectionContract $connection = null)
    {
    }
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

    public function compileSql(QueryType $type, QueryDefinition $query): string
    {
        return match ($type) {
            QueryType::SELECT => $this->compileSelect($query),
            QueryType::INSERT => $this->compileInsert($query),
            QueryType::UPDATE => $this->compileUpdate($query),
            QueryType::DELETE => $this->compileDelete($query),
        };
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function substituteBindingsIntoRawSql(string $sql, array $bindings): string
    {
        if ($this->connection === null) {
            throw new \RuntimeException('Connection required for substituteBindingsIntoRawSql');
        }

        $bindings = array_map(fn ($value) => $this->connection->quote($value), $bindings);

        $query = '';
        $isStringLiteral = false;

        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            $nextChar = $sql[$i + 1] ?? null;

            // Handle escaped characters and string literal boundaries
            if (in_array($char . $nextChar, ["\'", "''", '??'], true)) {
                $query .= $char . $nextChar;
                $i += 1;
            } elseif ($char === "'") {
                $query .= $char;
                $isStringLiteral = ! $isStringLiteral;
            } elseif ($char === '?' && ! $isStringLiteral) {
                $query .= array_shift($bindings) ?? '?';
            } else {
                $query .= $char;
            }
        }

        return $query;
    }
}
