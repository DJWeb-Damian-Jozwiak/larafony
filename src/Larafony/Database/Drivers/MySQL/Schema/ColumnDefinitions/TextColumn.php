<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions;

class TextColumn extends \Larafony\Framework\Database\Base\Schema\Columns\TextColumn
{
    public function getSqlDefinition(): string
    {
        $sql = "{$this->name} {$this->type}";
        $sql .= $this->getNullableSqlDefinition();
        return $sql;
    }

    /**
     * @param array<string, mixed> $description
     */
    public static function fromArrayDescription(array $description): static
    {
        return new TextColumn(
            $description['Field'],
            $description['Null'] === 'YES'
        );
    }

    protected function getNullableSqlDefinition(): string
    {
        return $this->nullable ? 'NULL ' : 'NOT NULL ';
    }
}
