<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema\Columns;

abstract class DateTimeColumn extends BaseColumn
{
    public function __construct(
        string $name,
        bool $nullable = true,
        mixed $default = null,
        public readonly ?string $onUpdate = null,
        public readonly int $precision = 0,
        string $type = 'DATETIME',
    ) {
        parent::__construct($name, $type, $nullable, $default);
    }

    public function current(string $default = 'CURRENT_TIMESTAMP'): static
    {
        return clone($this, ['default' => $default]);
    }

    public function currentOnUpdate(string $default = 'ON UPDATE CURRENT_TIMESTAMP'): static
    {
        return clone($this, ['onUpdate' => $default]);
    }

    public function precision(int $precision): static
    {
        return clone($this, ['precision' => $precision]);
    }

    abstract public function getDefaultValueDefinition(): string;
    abstract public function getOnUpdateDefinition(): string;
}
