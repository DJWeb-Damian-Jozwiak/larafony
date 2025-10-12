<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Schema;

use Larafony\Framework\Database\Base\Schema\Columns\BaseColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\DateTimeColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\EnumColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\IntColumn;
use Larafony\Framework\Database\Drivers\MySQL\Schema\ColumnDefinitions\StringColumn;

class ColumnFactory extends \Larafony\Framework\Database\Base\Schema\Columns\ColumnFactory
{
    public function create(array $description): BaseColumn
    {
        $callback = $this->getMappings()[strtolower($description['Type'])] ??
            throw new \InvalidArgumentException('Invalid column type ' . $description['Type']);
        return $callback($description);
    }
    private function getMappings(): array
    {
        return [
            'int' => IntColumn::fromArrayDescription(...),
            'bigint' => IntColumn::fromArrayDescription(...),
            'smallint' => IntColumn::fromArrayDescription(...),
            'tinyint' => IntColumn::fromArrayDescription(...),
            'mediumint' => IntColumn::fromArrayDescription(...),
            'varchar' => StringColumn::fromArrayDescription(...),
            'char' => StringColumn::fromArrayDescription(...),
            'text' => StringColumn::fromArrayDescription(...),
            'mediumtext' => StringColumn::fromArrayDescription(...),
            'longtext' => StringColumn::fromArrayDescription(...),
            'datetime' => DateTimeColumn::fromArrayDescription(...),
            'timestamp' => DateTimeColumn::fromArrayDescription(...),
            'date' => DateTimeColumn::fromArrayDescription(...),
            'time' => DateTimeColumn::fromArrayDescription(...),
            'enum' => EnumColumn::fromArrayDescription(...),
        ];
    }
}
