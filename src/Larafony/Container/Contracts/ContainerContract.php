<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Contracts;

use Psr\Container\ContainerInterface;

interface ContainerContract extends ContainerInterface
{
    /**
     * Bind a simple value to the container.
     */
    public function bind(string $key, string|int|float|bool|null $value): void;

    #[\NoDiscard]
    public function getBinding(string $key): string|int|float|bool|null;

    public function set(string $key, mixed $value): self;
}