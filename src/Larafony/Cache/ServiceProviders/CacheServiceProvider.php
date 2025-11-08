<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\ServiceProviders;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\Cache\CacheItemPool;
use Larafony\Framework\Cache\Factories\StorageFactory;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Psr\Cache\CacheItemPoolInterface;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register PSR-6 cache bindings
     *
     * @return array<string, class-string>
     */
    public function providers(): array
    {
        return [
            CacheItemPoolInterface::class => CacheItemPool::class,
        ];
    }

    public function boot(ContainerContract $container): void
    {
        parent::boot($container);

        $config = $container->get(ConfigContract::class);
        $driver = $config->get('cache.default', 'file');

        // Create storage and pool
        $factory = new StorageFactory();
        $pool = $factory->create($driver);

        // Register pool instance in container
        $container->set(CacheItemPoolInterface::class, $pool);

        // Initialize Cache singleton
        $cache = Cache::instance();
        $cache->init($pool);

        // Register Cache instance in container
        $container->set(Cache::class, $cache);
    }
}
