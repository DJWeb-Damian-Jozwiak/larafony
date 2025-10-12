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

    public function create(string $table, Closure $callback): void
    {
        $tableDefinition = new TableDefinition($table);
        $callback($tableDefinition);
        $this->connection->query($this->grammar->compileCreate($tableDefinition));
    }

    public function table(string $table, Closure $callback): void
    {
        $tableDefinition = new DatabaseInfo($this->connection)->getTable($table);
        $callback($tableDefinition);
        $this->connection->query($this->grammar->compileAddColumns($tableDefinition));
        $this->connection->query($this->grammar->compileModifyColumns($tableDefinition));
        $this->connection->query($this->grammar->compileDropColumns($tableDefinition));
    }

    public function drop(string $table): void
    {
        $this->connection->query($this->grammar->compileDropTable($table));
    }

    public function dropIfExists(string $table): void
    {
        $this->connection->query($this->grammar->compileDropTable($table, ifExists: true));
    }

    /**
     * @return array<int, string>
     */
    public function getColumnListing(string $table): array
    {
        return new DatabaseInfo($this->connection)->getTable($table)->columnNames;
    }
}
