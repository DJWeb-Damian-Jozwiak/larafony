<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\Builders;

use Larafony\Framework\Database\Base\Schema\Columns\BaseColumn;
use Larafony\Framework\Database\Base\Schema\IndexDefinitions\IndexDefinition;
use Larafony\Framework\Database\Base\Schema\TableDefinition;

class CreateTableBuilder extends \Larafony\Framework\Database\Base\Schema\Builders\Builders\CreateTableBuilder
{
    public function build(TableDefinition $table): string
    {
        $tableName = $table->tableName;
        $columns = $table->columns;
        $columnDefinitions = array_map(static fn (BaseColumn $column) => $column->getSqlDefinition(), $columns);
        return sprintf(
            'CREATE TABLE %s (%s);',
            $tableName,
            implode(', ', $columnDefinitions),
        ) . "\n" . $this->addIndexes($table);
    }

    private function addIndexes(TableDefinition $table): string
    {
        $indexes = $table->indexes;
        $indexDefinitions = array_map(static fn (IndexDefinition $index) => $index->getSqlDefinition(), $indexes);
        return implode("\n", $indexDefinitions);
    }
}
