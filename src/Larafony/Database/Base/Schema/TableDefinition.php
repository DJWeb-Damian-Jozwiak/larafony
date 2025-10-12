<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema;

use Larafony\Framework\Database\Base\Schema\Columns\BaseColumn;
use Larafony\Framework\Database\Base\Schema\Columns\DateTimeColumn;
use Larafony\Framework\Database\Base\Schema\Columns\IntColumn;
use Larafony\Framework\Database\Base\Schema\Columns\StringColumn;
use Larafony\Framework\Database\Base\Schema\Columns\TextColumn;
use Larafony\Framework\Database\Base\Schema\IndexDefinitions\IndexDefinition;
use Larafony\Framework\Database\Base\Schema\IndexDefinitions\NormalIndex;
use Larafony\Framework\Database\Base\Schema\IndexDefinitions\PrimaryIndex;
use Larafony\Framework\Database\Base\Schema\IndexDefinitions\UniqueIndex;

abstract class TableDefinition
{
    /**
     * @var array<string, IndexDefinition>
     */
    public protected(set) array $indexes = [];

    /**
     * @var  array<int, string> $columnNames
     */
    public array $columnNames {
        get => array_keys($this->columns);
    }

    /**
     * @var array<int, string> $numericColumns
     */
    protected array $numericColumns = ['INT', 'BIGINT', 'SMALLINT', 'MEDIUMINT', 'TINYINT'];
    /**
     * @var array<int, string> $stringColumns
     */
    protected array $stringColumns = ['VARCHAR', 'CHAR', 'UUID'];

    /**
     * @param array<string, BaseColumn> $columns
     */
    public function __construct(public readonly string $tableName, public protected(set) array $columns = [])
    {
    }
    public function id(string $column = 'id', string $type = 'INT'): IntColumn|StringColumn
    {
        $id = match (true) {
            in_array($type, $this->numericColumns) => $this->integer($column, $type)
                ->autoIncrement(true)->unsigned(false)->nullable(false),
            in_array($type, $this->stringColumns) => $this->string($column, 255, $type)->nullable(false),
            default => throw new \InvalidArgumentException('Invalid column type'),
        };
        $this->addColumn($id);
        $this->primary($column);
        return $id;
    }

    public function change(string $column): BaseColumn
    {
        $msg = sprintf('Column "%s" not found', $column);
        $column = $this->columns[$column] ?? throw new \InvalidArgumentException($msg);
        return $column->change();
    }

    public function drop(string $column): void
    {
        $msg = sprintf('Column "%s" not found', $column);
        $column = $this->columns[$column] ?? throw new \InvalidArgumentException($msg);
        $column->delete();
    }

    public function timestamps(): void
    {
        $this->dateTime('created_at')->nullable(false)->current();
        $this->dateTime('updated_at')->nullable(false)->currentOnUpdate();
    }

    public function softDeletes(): void
    {
        $this->dateTime('deleted_at')->nullable(true);
    }

    abstract public function integer(string $column, string $type = 'INT'): IntColumn;
    abstract public function bigInteger(string $column, string $type = 'MEDIUMINT'): IntColumn;
    abstract public function smallInteger(string $column, string $type = 'MEDIUMINT'): IntColumn;
    abstract public function string(string $column, int $length = 255, string $type = 'VARCHAR'): StringColumn;
    abstract public function char(string $column, int $length = 255, string $type = 'CHAR'): StringColumn;
    abstract public function text(string $column, string $type = 'TEXT'): TextColumn;
    abstract public function mediumText(string $column, string $type = 'MEDIUMTEXT'): TextColumn;
    abstract public function longText(string $column, string $type = 'LONGTEXT'): TextColumn;
    abstract public function json(string $column, string $type = 'JSON'): TextColumn;
    abstract public function dateTime(string $column, string $type = 'DATETIME'): DateTimeColumn;
    abstract public function timestamp(string $column, string $type = 'TIMESTAMP'): DateTimeColumn;
    abstract public function time(string $column, string $type = 'TIME'): DateTimeColumn;
    abstract public function date(string $column, string $type = 'DATE'): DateTimeColumn;
    /**
     * @param string|array<int, string> $columns
     */
    abstract public function index(string|array $columns, ?string $indexName = null): NormalIndex;
    /**
     * @param string|array<int, string> $columns
     */
    abstract public function primary(string|array $columns, ?string $indexName = null): PrimaryIndex;
    /**
     * @param string|array<int, string> $columns
     */
    abstract public function unique(string|array $columns, ?string $indexName = null): UniqueIndex;

    protected function addIndex(IndexDefinition $index): void
    {
        $this->indexes[$index->indexName] = $index;
    }

    protected function addColumn(BaseColumn $column): void
    {
        $this->columns[$column->name] = $column;
    }
}
