<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Contracts;

interface StorageFactoryContract
{
    /**
     * @param array<string, mixed> $config
     */
    public function create(array $config): StorageContract;
}
