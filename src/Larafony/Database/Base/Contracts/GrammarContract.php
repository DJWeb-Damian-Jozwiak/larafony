<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Contracts;

use Larafony\Framework\Database\Base\Schema\TableDefinition;

interface GrammarContract
{
    public function compileCreate(TableDefinition $table): string;
    public function compileAddColumns(TableDefinition $table): string;
    public function compileModifyColumns(TableDefinition $table): string;
    public function compileDropColumns(TableDefinition $table): string;
    public function compileDropTable(string $table, bool $ifExists = false): string;
}
