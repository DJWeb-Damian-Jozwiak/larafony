<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions;

class StringColumn extends \Larafony\Framework\Database\Base\Schema\Columns\StringColumn
{
    public function getSqlDefinition(): string
    {
        $sql = "{$this->name} {$this->type}({$this->length}) ";
        $sql .= $this->getNullableSqlDefinition();
        return trim($sql . $this->getDefaultValueSqlDefinition());
    }

    public static function fromArrayDescription(array $description): static
    {
        preg_match('/varchar|char\((\d+)\)/i', $description['Type'], $matches);
        $length = $matches[1] ?? 255;
        return new StringColumn(
            $description['Field'],
            $description['Null'] === 'YES',
            $description['Default'],
            (int) $length,
            $description['Type']
        );
    }

    protected function getNullableSqlDefinition(): string
    {
        return $this->nullable ? 'NULL ' : 'NOT NULL ';
    }

    protected function getDefaultValueSqlDefinition(): string
    {
        return $this->default !== null ? "DEFAULT '{$this->default}' " : '';
    }
}
