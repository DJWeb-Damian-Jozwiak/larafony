<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\Builders;

use Larafony\Framework\Database\Base\Schema\Columns\BaseColumn;
use Larafony\Framework\Database\Base\Schema\TableDefinition;

class ChangeColumns extends \Larafony\Framework\Database\Base\Schema\Builders\Builders\ChangeColumns
{
    #[\NoDiscard]
    public function build(TableDefinition $table): string
    {
        $columns = $table->columns;
        $columns = array_filter(
            $columns,
            static fn (BaseColumn $column) => $column->modified
        );
        if ($columns === []) {
            return '';
        }
        $tableName = $table->tableName;
        $columnDefinitions = array_map(static fn (BaseColumn $column) => $column->getSqlDefinition(), $columns);
        return sprintf(
            'CREATE TABLE %s (%s);',
            $tableName,
            implode(', ', $columnDefinitions)
        );
    }
}
