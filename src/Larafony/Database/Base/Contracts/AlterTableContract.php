<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Contracts;

use Larafony\Framework\Database\Base\Schema\TableDefinition;

interface AlterTableContract
{
    #[\NoDiscard]
    public function build(TableDefinition $table): string;
}
