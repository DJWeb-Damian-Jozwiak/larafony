<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema\IndexDefinitions;

abstract readonly class IndexDefinition
{
    public string $columns;

    public string $indexName;

    /**
     * @param array<int, string>|string $columns
     */
    public function __construct(
        public string $tableName,
        array|string $columns,
        ?string $indexName = null,
        string $type = 'index'
    ) {
        $this->indexName = $indexName ?? $this->generateIndexName($type, $columns);
        $this->columns = is_array($columns) ? implode(', ', $columns) : $columns;
    }

    abstract public function getSqlDefinition(): string;

    /**
     * Get inline SQL definition for use within CREATE TABLE statement
     */
    abstract public function getInlineSqlDefinition(): string;

    /**
     * @param array<int, string>|string $columns
     */
    protected function generateIndexName(string $type, array|string $columns): string
    {
        $columns = is_array($columns) ? implode('_', $columns) : $columns;
        $index = strtolower($this->tableName . '_' . $columns . '_' . $type);

        return str_replace(['-', '.'], '_', $index);
    }
}
