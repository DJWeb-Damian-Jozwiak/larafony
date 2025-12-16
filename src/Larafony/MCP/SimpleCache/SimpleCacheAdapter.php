<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP\SimpleCache;

use DateInterval;
use Larafony\Framework\Cache\Cache;
use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 SimpleCache adapter wrapping Larafony's PSR-6 Cache.
 *
 * MCP SDK requires PSR-16 for session storage and discovery caching.
 * This adapter bridges Larafony's Cache to the PSR-16 interface.
 */
final class SimpleCacheAdapter implements CacheInterface
{
    public function __construct(
        private readonly Cache $cache,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return $this->cache->put($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->cache->forget($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keyArray = $keys instanceof \Traversable ? iterator_to_array($keys) : $keys;
        $results = $this->cache->getMultiple($keyArray);

        foreach ($keyArray as $key) {
            yield $key => $results[$key] ?? $default;
        }
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $valueArray = $values instanceof \Traversable ? iterator_to_array($values) : $values;

        return $this->cache->putMultiple($valueArray, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $keyArray = $keys instanceof \Traversable ? iterator_to_array($keys) : $keys;

        return $this->cache->forgetMultiple($keyArray);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }
}
