<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema\Columns;

abstract class TextColumn extends BaseColumn
{
    public function __construct(
        string $name,
        bool $nullable = true,
        string $type = 'TEXT',
    ) {
        parent::__construct($name, $type, $nullable);
    }
}
