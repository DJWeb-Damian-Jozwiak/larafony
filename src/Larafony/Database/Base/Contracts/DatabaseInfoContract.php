<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Contracts;

use Larafony\Framework\Database\Base\Schema\TableDefinition;

interface DatabaseInfoContract
{
    /**
     * @return array<int, string>
     */
    public function getTables(): array;

    public function getTable(string $tableName): TableDefinition;
}
