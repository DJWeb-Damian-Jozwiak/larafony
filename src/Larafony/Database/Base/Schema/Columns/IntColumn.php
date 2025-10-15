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
        public bool $unsigned = false,
        public bool $autoIncrement = false,
        string $type = 'INT',
    ) {
        parent::__construct($name, $type, $nullable, $default);
    }

    public function unsigned(bool $unsigned): static
    {
        $this->unsigned = $unsigned;
        return $this;
    }

    public function length(int $length): static
    {
        $this->length = $length;
        return $this;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }

    public function autoIncrement(bool $autoIncrement): static
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    abstract protected function getUnsignedSqlDefinition(): string;
    abstract protected function getAutoIncrementSqlDefinition(): string;
    abstract protected function getDefaultValueSqlDefinition(): string;
}
