<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache;

use DateInterval;
use Larafony\Framework\Cache\Factories\StorageFactory;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Psr\Cache\CacheItemPoolInterface;

class Cache
{
    public CacheItemPoolInterface $pool {
        get => $this->poolInstance ?? throw new \RuntimeException('Cache not initialized. Call Cache::init() first.');
    }

    private ?CacheItemPoolInterface $poolInstance = null;
    private static ?ContainerContract $container = null;

    public function __construct(
        private readonly ConfigContract $config,
    ) {
    }

    /**
     * Set the container for static access (called by CacheServiceProvider)
     */
    public static function withContainer(ContainerContract $container): void
    {
        self::$container = $container;
    }

    /**
     * Get the Cache instance from the container (for use in entities, etc.)
     */
    public static function instance(): self
    {
        if (self::$container === null) {
            throw new \RuntimeException('Cache container not initialized. Register CacheServiceProvider first.');
        }

        return self::$container->get(self::class);
    }

    /**
     * Reset static container (for testing)
     */
    public static function empty(): void
    {
        self::$container = null;
    }

    public function init(CacheItemPoolInterface $pool): void
    {
        $this->poolInstance = $pool;
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

    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value);
    }

    /**
     * @param array<int, string> $keys
     */
    public function forgetMultiple(array $keys): bool
    {
        return $this->pool->deleteItems($keys);
    }

    public function clear(): bool
    {
        return $this->pool->clear();
    }

    /**
     * @param array<int, string> $keys
     *
     * @return array<string, mixed>
     */
    public function getMultiple(array $keys): array
    {
        $items = (array) $this->pool->getItems($keys);
        return array_map(static fn ($item) => $item->isHit() ? $item->get() : null, $items);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function putMultiple(array $values, DateInterval|int|null $ttl = null): bool
    {
        return array_all($values, fn ($value, $key) => $this->put($key, $value, $ttl));
    }

    public function increment(string $key, int $value = 1): int|false
    {
        // Use atomic operations if available (Redis/Memcached)
        if ($this->pool instanceof CacheItemPool
            && method_exists($this->pool->storage, 'increment')
        ) {
            return $this->pool->storage->increment($key, $value);
        }

        // Fallback for other storage drivers
        $current = $this->get($key, 0);
        if (! is_numeric($current)) {
            return false;
        }

        $new = (int) $current + $value;
        return $this->put($key, $new) ? $new : false;
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        // Use atomic operations if available (Redis/Memcached)
        if ($this->pool instanceof CacheItemPool
            && method_exists($this->pool->storage, 'decrement')
        ) {
            return $this->pool->storage->decrement($key, $value);
        }

        // Fallback
        return $this->increment($key, -$value);
    }

    /**
     * Get a tagged cache instance
     *
     * @param array<int, string> $tags
     *
     * @return TaggedCache
     */
    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this, $tags);
    }

    /**
     * Get a cache instance for a specific store
     *
     * @param string $name Store name from config/cache.php stores array
     *
     * @return self
     */
    public function store(string $name): self
    {
        $cache = new self($this->config);
        $pool = new StorageFactory($this->config)->create($name);
        $cache->init($pool);
        return $cache;
    }
}
