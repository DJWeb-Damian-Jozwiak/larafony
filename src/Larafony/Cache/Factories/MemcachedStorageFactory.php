<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Factories;

use Larafony\Framework\Cache\Contracts\StorageContract;
use Larafony\Framework\Cache\Contracts\StorageFactoryContract;
use Larafony\Framework\Cache\Storage\MemcachedStorage;
use Memcached;

class MemcachedStorageFactory implements StorageFactoryContract
{
    private static ?Memcached $memcached = null;
    public function create(array $config): StorageContract
    {
        self::$memcached ??= new Memcached();
        self::$memcached->addServer(
            $config['host'] ?? 'localhost',
            $config['port'] ?? 11211,
        );

        return new MemcachedStorage(
            self::$memcached,
            $config['prefix'] ?? 'cache:'
        );
    }
}