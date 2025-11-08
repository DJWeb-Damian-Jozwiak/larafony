<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Factories;

use Larafony\Framework\Cache\CacheItemPool;
use Larafony\Framework\Cache\Contracts\StorageContract;
use Larafony\Framework\Web\Config;
use Psr\Cache\CacheItemPoolInterface;

class StorageFactory
{
    public function create(string $storeName): CacheItemPoolInterface
    {
        $config = Config::get('cache.stores.' . $storeName);
        $configArray = $config instanceof \ArrayObject ? $config->getArrayCopy() : (array) $config;

        $driver = $configArray['driver'] ?? throw new \InvalidArgumentException(
            "Cache store '{$storeName}' must have 'driver' key in config (file, redis, or memcached)"
        );

        $storage = match ($driver) {
            'file' => $this->createFileStorage($configArray),
            'memcached' => $this->createMemcachedStorage($configArray),
            'redis' => $this->createRedisStorage($configArray),
            default => throw new \InvalidArgumentException("Unsupported cache driver: {$driver}"),
        };
        return new CacheItemPool($storage);
    }
    private function createFileStorage(array $config): StorageContract
    {
        return new FileStorageFactory()->create($config);
    }

    private function createMemcachedStorage(array $config): StorageContract
    {
        if(!extension_loaded('memcached')) {
            throw new \RuntimeException('Memcached extension is not loaded');
        }
        return new MemcachedStorageFactory()->create($config);
    }
    private function createRedisStorage(array $config): StorageContract
    {
        if(!extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension is not loaded');
        }
        return new RedisStorageFactory()->create($config);
    }
}