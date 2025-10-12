<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions;

readonly class PrimaryIndex extends \Larafony\Framework\Database\Base\Schema\IndexDefinitions\PrimaryIndex
{
    public function getSqlDefinition(): string
    {
        return "ALTER TABLE {$this->tableName} ADD PRIMARY KEY ({$this->columns})";
    }
}
