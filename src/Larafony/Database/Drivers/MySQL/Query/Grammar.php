<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Query\Contracts\GrammarContract;
use Larafony\Framework\Database\Base\Query\Enums\QueryType;
use Larafony\Framework\Database\Base\Query\QueryDefinition;
use Larafony\Framework\Database\Drivers\MySQL\Query\Constraints\IsEscapedChar;
use Larafony\Framework\Database\Drivers\MySQL\Query\Constraints\IsPlaceholder;
use Larafony\Framework\Database\Drivers\MySQL\Query\Constraints\IsStringBoundary;
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
    private string $result = '';
    public function __construct(private readonly ConnectionContract $connection)
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
        $this->result = '';
        $quotedBindings = array_map(fn ($value) => $this->connection->quote($value), $bindings);
        $inString = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if (IsEscapedChar::check($char, $next)) {
                $this->appendEscapedChar($char, $next);
                $i++;
            } elseif (IsStringBoundary::check($char, $next)) {
                $this->appendChar($char);
                $inString = ! $inString;
            } elseif (IsPlaceholder::check($char, $inString)) {
                $this->appendBinding($quotedBindings);
            } else {
                $this->appendChar($char);
            }
        }

        return $this->result;
    }

    private function appendEscapedChar(string $char, string $next): void
    {
        $this->result .= $char . $next;
    }

    private function appendChar(string $char): void
    {
        $this->result .= $char;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    private function appendBinding(array &$bindings): void
    {
        $this->result .= array_shift($bindings) ?? '?';
    }
}
