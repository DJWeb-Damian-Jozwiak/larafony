<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL;

use Closure;
use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Drivers\MySQL\Schema\DatabaseInfo;
use Larafony\Framework\Database\Drivers\MySQL\Schema\TableDefinition;

class SchemaBuilder extends \Larafony\Framework\Database\Base\Schema\SchemaBuilder
{
    public function __construct(ConnectionContract $connection)
    {
        parent::__construct($connection);
        $this->grammar = new Grammar();
    }

    #[\NoDiscard]
    public function create(string $table, Closure $callback): string
    {
        $tableDefinition = new TableDefinition($table);
        $callback($tableDefinition);
        return $this->grammar->compileCreate($tableDefinition);
    }

    #[\NoDiscard]
    public function table(string $table, Closure $callback): string
    {
        $tableDefinition = new DatabaseInfo($this->connection)->getTable($table);
        $callback($tableDefinition);

        $statements = array_filter([
            $this->grammar->compileAddColumns($tableDefinition),
            $this->grammar->compileModifyColumns($tableDefinition),
            $this->grammar->compileDropColumns($tableDefinition),
        ]);

        return implode(';' . PHP_EOL, $statements);
    }

    #[\NoDiscard]
    public function drop(string $table): string
    {
        return $this->grammar->compileDropTable($table);
    }

    #[\NoDiscard]
    public function dropIfExists(string $table): string
    {
        return $this->grammar->compileDropTable($table, ifExists: true);
    }

    /**
     * @return array<int, string>
     */
    #[\NoDiscard]
    public function getColumnListing(string $table): array
    {
        return new DatabaseInfo($this->connection)->getTable($table)->columnNames;
    }
}
