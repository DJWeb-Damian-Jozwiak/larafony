<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema;

use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\DateTimeColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\IntColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\StringColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\TextColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions\NormalIndex;
use Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions\PrimaryIndex;
use Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions\UniqueIndex;

class TableDefinition extends \Larafony\Framework\Database\Base\Schema\TableDefinition
{
    public function integer(string $column, string $type = 'INT'): IntColumn
    {
        return new IntColumn($column, type: $type);
    }

    public function bigInteger(string $column, string $type = 'MEDIUMINT'): IntColumn
    {
        return $this->integer($column, $type)->length(20);
    }
    public function smallInteger(string $column, string $type = 'MEDIUMINT'): IntColumn
    {
        return $this->integer($column, $type)->length(6);
    }
    public function string(string $column, int $length = 255, string $type = 'VARCHAR'): StringColumn
    {
        return new StringColumn($column, length: $length, type: $type);
    }
    public function char(string $column, int $length = 255, string $type = 'CHAR'): StringColumn
    {
        return $this->string($column, $length, $type);
    }
    public function text(string $column, string $type = 'TEXT'): TextColumn
    {
        return new TextColumn($column, type: $type);
    }
    public function mediumText(string $column, string $type = 'MEDIUMTEXT'): TextColumn
    {
        return $this->text($column, $type);
    }
    public function longText(string $column, string $type = 'LONGTEXT'): TextColumn
    {
        return $this->text($column, $type);
    }
    public function json(string $column, string $type = 'JSON'): TextColumn
    {
        return $this->text($column, $type);
    }
    public function dateTime(string $column, string $type = 'DATETIME'): DateTimeColumn
    {
        return new DateTimeColumn($column, type: $type);
    }
    public function timestamp(string $column, string $type = 'TIMESTAMP'): DateTimeColumn
    {
        return $this->dateTime($column, $type);
    }
    public function time(string $column, string $type = 'TIME'): DateTimeColumn
    {
        return $this->dateTime($column, $type);
    }
    public function date(string $column, string $type = 'DATE'): DateTimeColumn
    {
        return $this->dateTime($column, $type);
    }

    /**
     * @param string|array<int, string> $columns
     */
    public function index(string|array $columns, ?string $indexName = null): NormalIndex
    {
        return new NormalIndex($this->tableName, $columns, $indexName);
    }
    /**
     * @param string|array<int, string> $columns
     */
    public function primary(string|array $columns, ?string $indexName = null): PrimaryIndex
    {
        return new PrimaryIndex($this->tableName, $columns, $indexName, type: 'primary');
    }
    /**
     * @param string|array<int, string> $columns
     */
    public function unique(string|array $columns, ?string $indexName = null): UniqueIndex
    {
        return new UniqueIndex($this->tableName, $columns, $indexName, type: 'unique');
    }
}
