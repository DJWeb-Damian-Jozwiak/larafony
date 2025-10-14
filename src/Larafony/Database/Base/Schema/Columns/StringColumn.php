<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema\Columns;

abstract class StringColumn extends BaseColumn
{
    public function __construct(
        string $name,
        bool $nullable = true,
        mixed $default = null,
        public readonly int $length = 255,
        string $type = 'VARCHAR',
    ) {
        parent::__construct($name, $type, $nullable, $default);
    }

    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }

    abstract protected function getDefaultValueSqlDefinition(): string;
}
