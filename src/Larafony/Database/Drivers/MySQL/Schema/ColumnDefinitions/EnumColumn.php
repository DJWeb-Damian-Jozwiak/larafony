<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions;

class EnumColumn extends \Larafony\Framework\Database\Base\Schema\Columns\EnumColumn
{
    public function getSqlDefinition(): string
    {
        $values = implode(',', array_map(static fn ($v) => "'{$v}'", $this->values));
        $sql = "{$this->name} {$this->type}({$values}) ";
        $sql .= $this->getNullableSqlDefinition();
        return trim($sql . $this->getDefaultDefinition());
    }

    public static function fromArrayDescription(array $description): static
    {
        preg_match('/enum\((.*)\)/i', $description['Type'], $matches);
        $values = str_getcsv($matches[1] ?? '');
        $values = array_filter($values, static fn (string|null $value) => (bool) $value);
        $values = array_map(
            static fn (string $value) => str_replace("'", '', $value),
            $values
        );
        return new EnumColumn(
            $description['Field'],
            $values,
            $description['Null'] === 'YES',
            $description['Default']
        );
    }

    protected function getNullableSqlDefinition(): string
    {
        return $this->nullable ? 'NULL ' : 'NOT NULL ';
    }

    protected function getDefaultDefinition(): string
    {
        return $this->default !== null ? "DEFAULT '{$this->default}' " : '';
    }
}
