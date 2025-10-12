<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL;

use Larafony\Framework\Database\Base\Contracts\GrammarContract;
use Larafony\Framework\Database\Base\Schema\TableDefinition;
use Larafony\Framework\Database\Drivers\MySQL\Schema\Builders\AddColumns;
use Larafony\Framework\Database\Drivers\MySQL\Schema\Builders\ChangeColumns;
use Larafony\Framework\Database\Drivers\MySQL\Schema\Builders\CreateTableBuilder;
use Larafony\Framework\Database\Drivers\MySQL\Schema\Builders\DropColumns;

class Grammar implements GrammarContract
{
    public function compileCreate(TableDefinition $table): string
    {
        return new CreateTableBuilder()->build($table);
    }

    public function compileAddColumns(TableDefinition $table): string
    {
        return new AddColumns()->build($table);
    }

    public function compileModifyColumns(TableDefinition $table): string
    {
        return new ChangeColumns()->build($table);
    }

    public function compileDropColumns(TableDefinition $table): string
    {
        return new DropColumns()->build($table);
    }

    public function compileDropTable(string $table, bool $ifExists = false): string
    {
        return sprintf('DROP TABLE %s%s', $ifExists ? 'IF EXISTS ' : '', $table);
    }
}
