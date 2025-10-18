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

        // Add inline index definitions to CREATE TABLE
        $inlineIndexes = $this->getInlineIndexes($table);

        $allDefinitions = array_merge($columnDefinitions, $inlineIndexes);

        return sprintf(
            'CREATE TABLE %s (%s);',
            $tableName,
            implode(', ', $allDefinitions),
        );
    }

    /**
     * Get inline index definitions for CREATE TABLE statement
     *
     * @return array<int, string>
     */
    private function getInlineIndexes(TableDefinition $table): array
    {
        $indexes = $table->indexes;
        return array_map(
            static fn (IndexDefinition $index) => $index->getInlineSqlDefinition(),
            $indexes,
        );
    }
}
