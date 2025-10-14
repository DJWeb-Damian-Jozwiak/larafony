<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema\Columns;

abstract class DateTimeColumn extends BaseColumn
{
    public function __construct(
        string $name,
        bool $nullable = true,
        mixed $default = null,
        public ?string $onUpdate = null,
        public int $precision = 0,
        string $type = 'DATETIME',
    ) {
        parent::__construct($name, $type, $nullable, $default);
    }

    public function current(string $default = 'CURRENT_TIMESTAMP'): static
    {
        $this->default = $default;
        return $this;
    }

    public function currentOnUpdate(string $default = 'ON UPDATE CURRENT_TIMESTAMP'): static
    {
        $this->onUpdate = $default;
        return $this;
    }

    public function precision(int $precision): static
    {
        $this->precision = $precision;
        return $this;
    }

    abstract public function getDefaultValueDefinition(): string;
    abstract public function getOnUpdateDefinition(): string;
}
