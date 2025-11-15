<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Factories;

use Larafony\Framework\Cache\Contracts\StorageContract;
use Larafony\Framework\Cache\Contracts\StorageFactoryContract;
use Larafony\Framework\Cache\Storage\RedisStorage;
use Redis;

class RedisStorageFactory implements StorageFactoryContract
{
    private static ?Redis $redis = null;
    /**
     * @param array<string, mixed> $config
     */
    public function create(array $config): StorageContract
    {
        $isNewConnection = self::$redis === null;
        self::$redis ??= new Redis();

        if ($isNewConnection || !self::$redis->isConnected()) {
            self::$redis->connect(
                $config['host'] ?? 'localhost',
                $config['port'] ?? 6379,
                $config['timeout'] ?? 0.0,
            );

            if (isset($config['password'])) {
                self::$redis->auth($config['password']);
            }
        }

        if (isset($config['database'])) {
            self::$redis->select($config['database']);
        }

        $storage = new RedisStorage(self::$redis, $config['prefix'] ?? 'cache:');
        if (isset($config['max_memory'])) {
            $storage->maxCapacity($config['max_memory']);
        }
        if (isset($config['eviction_policy'])) {
            $storage->withEvictionPolicy($config['eviction_policy']);
        }
        return $storage;
    }
}