<?php

declare(strict_types=1);

namespace Larafony\Framework\Container;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\Deferred\ProviderLoader;
use Larafony\Framework\Container\Deferred\ProviderRegistry;
use Larafony\Framework\Container\Deferred\SingletonCache;

/**
 * Manages deferred service providers for lazy loading.
 *
 * Deferred providers are only registered when their services are first requested,
 * improving application boot time.
 */
final class DeferredServiceLoader
{
    private readonly ProviderRegistry $registry;
    private readonly SingletonCache $cache;
    private readonly ProviderLoader $loader;

    public function __construct(
        private readonly ContainerContract $container,
    ) {
        $this->registry = new ProviderRegistry();
        $this->cache = new SingletonCache();
        $this->loader = new ProviderLoader($container, $this->registry);
    }

    /**
     * Register a deferred provider by analyzing its #[Deferred] attribute.
     *
     * @param class-string $providerClass
     */
    public function registerDeferred(string $providerClass): void
    {
        $this->registry->register($providerClass);
    }

    /**
     * Check if a service is deferred AND not yet loaded.
     */
    public function isDeferred(string $service): bool
    {
        $providerClass = $this->registry->getProviderFor($service);

        return $providerClass && ! $this->registry->isLoaded($providerClass);
    }

    /**
     * Load a deferred service and return its instance.
     * All deferred services are cached as singletons.
     */
    public function load(string $service): mixed
    {
        $providerClass = $this->registry->getProviderFor($service);

        // Load provider if needed
        $this->loader->load($providerClass);

        // Resolve instance from container
        $instance = $this->container->get($service);

        $this->cache->set($service, $instance);

        return $instance;
    }

    /**
     * Get all deferred services.
     *
     * @return array<class-string>
     */
    public function getDeferredServices(): array
    {
        return $this->registry->getServices();
    }

    /**
     * Check if a provider has been loaded.
     *
     * @param class-string $providerClass
     */
    public function isProviderLoaded(string $providerClass): bool
    {
        return $this->registry->isLoaded($providerClass);
    }

    /**
     * Get cached singleton instance if exists.
     */
    public function getCached(string $service): ?object
    {
        return $this->cache->get($service);
    }
}
