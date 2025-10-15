<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions;

readonly class UniqueIndex extends \Larafony\Framework\Database\Base\Schema\IndexDefinitions\UniqueIndex
{
    public function getSqlDefinition(): string
    {
        return "CREATE UNIQUE INDEX {$this->indexName} ON {$this->tableName} ({$this->columns})";
    }

    public function getInlineSqlDefinition(): string
    {
        return "UNIQUE KEY {$this->indexName} ({$this->columns})";
    }
}
