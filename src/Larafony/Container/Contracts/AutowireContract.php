<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Contracts;

interface AutowireContract
{
    public function instantiate(string $className): object;
}
