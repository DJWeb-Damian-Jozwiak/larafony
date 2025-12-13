<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Factories;

use Larafony\Framework\Cache\Contracts\StorageFactoryContract;
use Larafony\Framework\Cache\Storage\FileStorage;

class FileStorageFactory implements StorageFactoryContract
{
    public function create(array $config): FileStorage
    {
        $storage = new FileStorage($config['path']);
        $storage->maxCapacity($config['max_items'] ?? 1000);
        return $storage;
    }
}
