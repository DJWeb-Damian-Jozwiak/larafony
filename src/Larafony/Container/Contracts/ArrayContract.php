<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Contracts;

interface ArrayContract
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $default = null): void;
    public function has(string $key): bool;
}
