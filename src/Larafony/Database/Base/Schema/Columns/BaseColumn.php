<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema\Columns;

abstract class BaseColumn
{
    public protected(set) bool $modified = false;
    public protected(set) bool $deleted = false;
    public protected(set) bool $existsInDatabase = false;

    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public bool $nullable = true,
        protected mixed $default = null,
    ) {
    }

    public function markAsExisting(): static
    {
        $this->existsInDatabase = true;
        return $this;
    }
    public function change(): static
    {
        $this->modified = true;
        return $this;
    }
    public function delete(): static
    {
        $this->deleted = true;
        return $this;
    }
    public function nullable(bool $nullable): static
    {
        $this->nullable = $nullable;
        return $this;
    }

    abstract public function getSqlDefinition(): string;
    /**
     * @param array<string, mixed> $description
     */
    abstract public static function fromArrayDescription(array $description): static;
    abstract protected function getNullableSqlDefinition(): string;
}
