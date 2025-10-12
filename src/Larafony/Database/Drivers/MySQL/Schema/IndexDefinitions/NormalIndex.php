<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions;

readonly class NormalIndex extends \Larafony\Framework\Database\Base\Schema\IndexDefinitions\NormalIndex
{
    public function getSqlDefinition(): string
    {
        return "CREATE INDEX {$this->indexName} ON {$this->tableName} ({$this->columns})";
    }
}
