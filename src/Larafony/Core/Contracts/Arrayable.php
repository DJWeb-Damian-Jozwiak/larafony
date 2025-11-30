<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Contracts;

interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(): array;
}
