<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions;

class DateTimeColumn extends \Larafony\Framework\Database\Base\Schema\Columns\DateTimeColumn
{
    public function getSqlDefinition(): string
    {
        $sql = "{$this->name} {$this->type} ";
        $sql .= $this->getNullableSqlDefinition();
        $sql .= $this->getDefaultValueDefinition();
        return trim($sql . $this->getOnUpdateDefinition());
    }

    public function getDefaultValueDefinition(): string
    {
        return $this->default !== null ? "DEFAULT {$this->default} " : '';
    }

    public function getOnUpdateDefinition(): string
    {
        return $this->onUpdate !== null ? 'ON UPDATE CURRENT_TIMESTAMP' : '';
    }

    public static function fromArrayDescription(array $description): static
    {
        return new DateTimeColumn(
            $description['Field'],
            $description['Null'] === 'YES',
            $description['Default'],
            $description['Extra'],
            $description['Type'],
        );
    }

    protected function getNullableSqlDefinition(): string
    {
        return $this->nullable ? 'NULL ' : 'NOT NULL ';
    }
}
