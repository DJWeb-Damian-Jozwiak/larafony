<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema\Columns;

use Larafony\Framework\Database\Base\Schema\Exceptions\UnsupportedColumnType;

abstract class EnumColumn extends BaseColumn
{
    /**
     * @param array<int, string|int> $values
     */
    public function __construct(
        string $name,
        public readonly array $values,
        bool $nullable = true,
        mixed $default = null,
    ) {
        parent::__construct($name, 'ENUM', $nullable, $default);
        if ($this->default !== null && ! in_array($this->default, $this->values)) {
            throw new UnsupportedColumnType();
        }
    }

    /**
     * @param array<int, string|int> $values
     */
    public function values(array $values): static
    {
        return clone($this, ['values' => $values]);
    }

    abstract protected function getDefaultDefinition(): string;
}
