<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Factories;

use Larafony\Framework\Cache\CacheItemPool;
use Larafony\Framework\Cache\Contracts\StorageContract;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Psr\Cache\CacheItemPoolInterface;

class StorageFactory
{
    public function __construct(
        private ConfigContract $config,
    ) {
    }

    public function create(string $storeName): CacheItemPoolInterface
    {
        $storeConfig = $this->config->get('cache.stores.' . $storeName);
        $configArray = $storeConfig instanceof \ArrayObject ? $storeConfig->getArrayCopy() : (array) $storeConfig;

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
    /**
     * @param array<string, mixed> $config
     */
    private function createFileStorage(array $config): StorageContract
    {
        return new FileStorageFactory()->create($config);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createMemcachedStorage(array $config): StorageContract
    {
        if (! extension_loaded('memcached')) {
            throw new \RuntimeException('Memcached extension is not loaded');
        }
        return new MemcachedStorageFactory()->create($config);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createRedisStorage(array $config): StorageContract
    {
        if (! extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension is not loaded');
        }
        return new RedisStorageFactory()->create($config);
    }
}
