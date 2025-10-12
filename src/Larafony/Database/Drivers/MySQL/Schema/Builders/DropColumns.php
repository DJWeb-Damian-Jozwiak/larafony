<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\Builders;

use Larafony\Framework\Database\Base\Schema\Columns\BaseColumn;
use Larafony\Framework\Database\Base\Schema\TableDefinition;

class DropColumns extends \Larafony\Framework\Database\Base\Schema\Builders\Builders\DropColumns
{
    #[\NoDiscard]
    public function build(TableDefinition $table): string
    {
        $columns = $table->columns;
        $columns = array_filter(
            $columns,
            static fn (BaseColumn $column) => $column->deleted
        );
        if ($columns === []) {
            return '';
        }
        $tableName = $table->tableName;
        $modifiedColumns = array_map(
            static fn (BaseColumn $column) => 'DROP COLUMN ' .$column->name,
            $columns
        );
        return 'ALTER TABLE ' .$tableName. ' ' .implode(', ', $modifiedColumns);
    }
}
