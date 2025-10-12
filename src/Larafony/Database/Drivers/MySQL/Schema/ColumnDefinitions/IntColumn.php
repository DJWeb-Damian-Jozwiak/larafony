<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions;

class IntColumn extends \Larafony\Framework\Database\Base\Schema\Columns\IntColumn
{
    public function getSqlDefinition(): string
    {
        $sql = "{$this->name} {$this->type}({$this->length})";
        $sql .= $this->getUnsignedSqlDefinition();
        $sql .= $this->getNullableSqlDefinition();
        $sql .= $this->getDefaultValueSqlDefinition();
        return $sql . $this->getAutoIncrementSqlDefinition();
    }

    /**
     * @param array<string, mixed> $description
     */
    public static function fromArrayDescription(array $description): static
    {
        preg_match('/int\((\d+)\)/i', $description['Type'], $matches);
        $length = $matches[1] ?? 11;
        $unsigned = str_contains($description['Type'], 'unsigned');
        $autoIncrement = $description['Extra'] === 'auto_increment';
        return new IntColumn(
            $description['Field'],
            $description['Null'] === 'YES',
            $description['Default'],
            (int) $length,
            $unsigned,
            $autoIncrement,
            $description['Type']
        );
    }

    protected function getNullableSqlDefinition(): string
    {
        return $this->nullable ? ' NULL' : ' NOT NULL';
    }

    protected function getUnsignedSqlDefinition(): string
    {
        return $this->unsigned ? ' UNSIGNED' : '';
    }

    protected function getAutoIncrementSqlDefinition(): string
    {
        return $this->autoIncrement ? ' AUTO_INCREMENT' : '';
    }

    protected function getDefaultValueSqlDefinition(): string
    {
        return $this->default !== null ? " DEFAULT {$this->default}" : '';
    }
}
