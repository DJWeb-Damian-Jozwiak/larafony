<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema\Columns;

abstract class IntColumn extends BaseColumn
{
    public function __construct(
        string $name,
        bool $nullable = true,
        mixed $default = null,
        public int $length = 11,
        public readonly bool $unsigned = false,
        public readonly bool $autoIncrement = false,
        string $type = 'INT',
    ) {
        parent::__construct($name, $type, $nullable, $default);
    }

    public function unsigned(bool $unsigned): static
    {
        return clone($this, ['unsigned' => $unsigned]);
    }

    public function length(int $length): static
    {
        return clone($this, ['length' => $length]);
    }

    public function default(mixed $default): static
    {
        return clone($this, ['default' => $default]);
    }

    public function autoIncrement(bool $autoIncrement): static
    {
        return clone($this, ['autoIncrement' => $autoIncrement]);
    }

    abstract protected function getUnsignedSqlDefinition(): string;
    abstract protected function getAutoIncrementSqlDefinition(): string;
    abstract protected function getDefaultValueSqlDefinition(): string;
}
