<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Storage;

use Larafony\Framework\Cache\Enums\RedisEvictionPolicy;
use Larafony\Framework\Clock\ClockFactory;
use Redis;

class RedisStorage extends BaseStorage
{
    public function __construct(
        private Redis $redis,
        private string $prefix = 'cmp_cache:'
    ) {
        $this->withEvictionPolicy(RedisEvictionPolicy::ALLKEYS_LFU);
    }

    /**
     * Set Redis eviction policy
     *
     * @param RedisEvictionPolicy $policy
     *
     * @return void
     */
    public function withEvictionPolicy(RedisEvictionPolicy $policy): void
    {
        $this->redis->config('SET', 'maxmemory-policy', $policy->value);
    }

    /**
     * Set maximum memory capacity
     *
     * @param int $size Size in bytes (e.g., 256 * 1024 * 1024 for 256MB)
     *
     * @return void
     */
    public function maxCapacity(int $size): void
    {
        $this->redis->config('SET', 'maxmemory', (string) $size);
    }

    /**
     * Get Redis connection info
     *
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        $info = $this->redis->info();
        return [
            'connected' => $this->redis->isConnected(),
            'memory_used' => $info['used_memory_human'] ?? 'N/A',
            'memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
            'total_keys' => $this->redis->dbSize(),
            'uptime_days' => isset($info['uptime_in_days']) ? (int) $info['uptime_in_days'] : 0,
        ];
    }

    /**
     * Batch set multiple items using Redis pipeline
     *
     * @param array<string, array<string, mixed>> $items Key-value pairs with expiry
     *
     * @return bool
     */
    public function withMultiple(array $items): bool
    {
        $pipe = $this->redis->multi(\Redis::PIPELINE);
        $currentTime = ClockFactory::now()->getTimestamp();

        foreach ($items as $key => $data) {
            $prefixedKey = $this->prefix . $key;
            $serialized = serialize($data);
            $compressed = $this->maybeCompress($serialized);

            $ttl = isset($data['expiry']) ? $data['expiry'] - $currentTime : 0;

            if ($ttl > 0) {
                $pipe->setex($prefixedKey, $ttl, $compressed);
            } else {
                $pipe->set($prefixedKey, $compressed);
            }
        }

        $results = $pipe->exec();

        // Check if all operations succeeded
        return ! in_array(false, $results, true);
    }

    /**
     * Batch get multiple items using Redis pipeline
     *
     * @param array<int, string> $keys
     *
     * @return array<string, array<string, mixed>|null>
     */
    public function getMultiple(array $keys): array
    {
        $prefixedKeys = array_map(
            fn ($key) => $this->prefix . $key,
            $keys
        );

        $values = $this->redis->mGet($prefixedKeys);

        $result = [];
        foreach ($keys as $index => $key) {
            $value = $values[$index];

            if ($value === false) {
                $result[$key] = null;
                continue;
            }

            $decompressed = $this->maybeDecompress($value);
            $unserialized = unserialize($decompressed);
            $result[$key] = is_array($unserialized) ? $unserialized : null;
        }

        return $result;
    }

    /**
     * Batch delete multiple items using Redis pipeline
     *
     * @param array<int, string> $keys
     *
     * @return bool
     */
    public function deleteMultiple(array $keys): bool
    {
        $prefixedKeys = array_map(
            fn ($key) => $this->prefix . $key,
            $keys
        );

        $deleted = $this->redis->del($prefixedKeys);

        // Returns number of keys deleted
        return $deleted > 0;
    }

    /**
     * Atomically increment a value
     *
     * @param string $key
     * @param int $value
     *
     * @return int The new value after increment
     */
    public function increment(string $key, int $value = 1): int
    {
        return $this->redis->incrBy($this->prefix . $key, $value);
    }

    /**
     * Atomically decrement a value
     *
     * @param string $key
     * @param int $value
     *
     * @return int The new value after decrement
     */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->redis->decrBy($this->prefix . $key, $value);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getFromBackend(string $key): ?array
    {
        $fullKey = $this->prefix . $key;
        $data = $this->redis->get($fullKey);

        if ($data === false) {
            return null;
        }

        // Decompress if needed
        $decompressed = $this->maybeDecompress($data);
        $unserialized = unserialize($decompressed);

        return is_array($unserialized) ? $unserialized : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function addToBackend(string $key, array $data): bool
    {
        $fullKey = $this->prefix . $key;
        $serialized = serialize($data);

        // Compress if needed
        $compressed = $this->maybeCompress($serialized);

        $ttl = isset($data['expiry']) ? $data['expiry'] - ClockFactory::now()->getTimestamp() : 0;

        if ($ttl > 0) {
            return $this->redis->setex($fullKey, $ttl, $compressed);
        }

        return $this->redis->set($fullKey, $compressed);
    }

    protected function deleteFromBackend(string $key): bool
    {
        // Redis del() returns number of keys deleted (0 or 1)
        // We return true even if key doesn't exist (idempotent operation)
        $this->redis->del($this->prefix . $key);
        return true;
    }

    protected function clearBackend(): bool
    {
        $iterator = null;
        $pattern = $this->prefix . '*';

        do {
            $keys = $this->redis->scan($iterator, $pattern, 100);
            if ($keys !== false && $keys !== []) {
                $this->redis->del($keys);
            }
        } while ($iterator > 0);

        return true;
    }
}
