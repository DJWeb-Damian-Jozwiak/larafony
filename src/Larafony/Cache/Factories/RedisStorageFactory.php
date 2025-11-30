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

        if ($isNewConnection || ! self::$redis->isConnected()) {
            self::$redis->connect(
                $config['host'] ?? 'localhost',
                $config['port'] ?? 6379,
                $config['timeout'] ?? 0.0,
            );

            if (isset($config['password'])) {
                self::$redis->auth($config['password']);
            }
        }

        $db = $config['database'] ?? 0;
        self::$redis->select($db);

        $storage = new RedisStorage(self::$redis, $config['prefix'] ?? 'cache:');
        $max_memory = $config['max_memory'] ?? 1024 ** 3;
        $storage->maxCapacity($max_memory);
        if (isset($config['eviction_policy'])) {
            $storage->withEvictionPolicy($config['eviction_policy']);
        }
        return $storage;
    }
}
