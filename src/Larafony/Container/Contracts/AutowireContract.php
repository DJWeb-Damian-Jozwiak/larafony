<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Contracts;

interface AutowireContract
{
    /**
     * Autowire and instantiate a class.
     *
     * @template T of object
     *
     * @param class-string<T> $className The name of the class to instantiate
     *
     * @return T The instantiated object
     */
    public function instantiate(string $className): object;
}
