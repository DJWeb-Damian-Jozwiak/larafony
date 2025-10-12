<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema\Columns;

abstract class ColumnFactory
{
    /**
     * @param array<string, mixed> $description
     */
    abstract public function create(array $description): BaseColumn;
}
