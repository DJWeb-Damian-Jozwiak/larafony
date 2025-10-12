<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema\Builders\Builders;

use Larafony\Framework\Database\Base\Schema\TableDefinition;

abstract class CreateTableBuilder
{
    abstract public function build(TableDefinition $table): string;
}
