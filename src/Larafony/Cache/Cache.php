<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache;

use DateInterval;
use Larafony\Framework\Cache\Factories\StorageFactory;
use Larafony\Framework\Web\Config;
use Psr\Cache\CacheItemPoolInterface;

class Cache
{
    public private(set) ?CacheItemPoolInterface $pool {
        get => $this->pool ?? throw new \RuntimeException('Cache not initialized. Call Cache::init() first.');
        set => $this->pool = $value;
    }

    private static ?Cache $cache = null;

    public static function instance(): Cache
    {
        self::$cache ??= new self();
        $driver = Config::get('cache.default');
        $pool = new StorageFactory()->create($driver);
        self::$cache->init($pool);
        return self::$cache;
    }

    /**
     * Reset singleton instance (for testing)
     *
     * @return void
     */
    public static function empty(): void
    {
        self::$cache = null;
    }

    public function init(CacheItemPoolInterface $pool): void
    {
        $this->pool = $pool;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->pool->getItem($key);
        return $item->isHit() ? $item->get() : $default;
    }

    public function put(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {

        $item = $this->pool->getItem($key);
        $item = $item->set($value)->expiresAfter($ttl);

        return $this->pool->save($item);
    }

    public function forget(string $key): bool
    {
        return $this->pool->deleteItem($key);
    }

    public function has(string $key): bool
    {
        return $this->pool->hasItem($key);
    }

    public function remember(string $key, DateInterval|int $ttl, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        self::put($key, $value, $ttl);

        return $value;
    }

    public function forever(string $key, mixed $value)
    {
        return $this->put($key, $value);
    }

    public function forgetMultiple(array $keys): bool
    {
        return $this->pool->deleteItems($keys);
    }

    public function clear(): bool
    {
        return $this->pool->clear();
    }

    public function getMultiple(array $keys): array
    {

        $items = (array)$this->pool->getItems($keys);
        return array_map(static fn ($item) => $item->isHit() ? $item->get() : null, $items);
    }

    public function putMultiple(array $values, DateInterval|int|null $ttl = null): bool
    {

        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->put($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function increment(string $key, int $value = 1): int|false
    {
        // Use atomic operations if available (Redis/Memcached)
        if (method_exists($this->pool->storage, 'increment')) {
            return $this->pool->storage->increment($key, $value);
        }

        // Fallback for other storage drivers
        $current = $this->get($key, 0);
        if (!is_numeric($current)) {
            return false;
        }

        $new = (int)$current + $value;
        return $this->put($key, $new) ? $new : false;
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        // Use atomic operations if available (Redis/Memcached)
        if (method_exists($this->pool->storage, 'decrement')) {
            return $this->pool->storage->decrement($key, $value);
        }

        // Fallback
        return $this->increment($key, -$value);
    }

    /**
     * Get a tagged cache instance
     *
     * @param array<int, string> $tags
     * @return TaggedCache
     */
    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this, $tags);
    }

    /**
     * Get cache warmer instance
     *
     * @return CacheWarmer
     */
    public function warmer(): CacheWarmer
    {
        return new CacheWarmer($this);
    }

    /**
     * Get a cache instance for a specific store
     *
     * @param string $name Store name from config/cache.php stores array
     * @return self
     */
    public function store(string $name): self
    {
        $cache = new self();
        $pool = new StorageFactory()->create($name);
        $cache->init($pool);
        return $cache;
    }
}