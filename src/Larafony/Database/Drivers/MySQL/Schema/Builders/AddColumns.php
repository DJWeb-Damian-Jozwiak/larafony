<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\Builders;

use Larafony\Framework\Database\Base\Schema\Columns\BaseColumn;
use Larafony\Framework\Database\Base\Schema\TableDefinition;

class AddColumns extends \Larafony\Framework\Database\Base\Schema\Builders\Builders\AddColumns
{
    #[\NoDiscard]
    public function build(TableDefinition $table): string
    {
        $columns = $table->columns;
        $columns = array_filter(
            $columns,
            static fn (BaseColumn $column) => ! $column->existsInDatabase && ! $column->modified && ! $column->deleted
        );
        if ($columns === []) {
            return '';
        }
        $tableName = $table->tableName;
        $definitions = array_map(
            static fn (BaseColumn $column) => 'ADD COLUMN ' .$column->getSqlDefinition(),
            $columns
        );
        return sprintf('ALTER TABLE %s %s;', $tableName, implode(', ', $definitions));
    }
}
