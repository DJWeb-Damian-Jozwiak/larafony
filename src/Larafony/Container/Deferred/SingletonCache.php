<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Deferred;

/**
 * Caches singleton instances of deferred services.
 * All deferred services are automatically cached as singletons.
 */
final class SingletonCache
{
    /**
     * @var array<class-string, object> Singleton instances cache
     */
    private array $cache = [];

    /**
     * Get cached instance for a service.
     */
    public function get(string $service): ?object
    {
        return $this->cache[$service] ?? null;
    }

    /**
     * Cache an instance for a service.
     */
    public function set(string $service, object $instance): void
    {
        $this->cache[$service] = $instance;
    }
}
