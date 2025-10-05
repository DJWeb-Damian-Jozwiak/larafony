<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Request;

final class AttributesManager
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly array $attributes = [],
    ) {
    }

    public function withAttribute(string $name, mixed $value): self
    {
        return clone($this, ['attributes' => [...$this->attributes, $name => $value]]);
    }

    public function withoutAttribute(string $name): self
    {
        $attributes = $this->attributes;
        unset($attributes[$name]);

        return clone($this, ['attributes' => $attributes]);
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }
}
