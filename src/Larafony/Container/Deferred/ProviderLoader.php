<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Deferred;

use Larafony\Framework\Container\Contracts\ContainerContract;

/**
 * Loads and bootstraps deferred service providers.
 */
final class ProviderLoader
{
    public function __construct(
        private readonly ContainerContract $container,
        private readonly ProviderRegistry $registry,
    ) {
    }

    /**
     * Load a provider and bootstrap it.
     *
     * @param class-string $providerClass
     */
    public function load(string $providerClass): void
    {
        if (! $this->registry->isLoaded($providerClass)) {
            // Mark as loaded BEFORE registering to prevent infinite loop
            $this->registry->markAsLoaded($providerClass);

            $provider = new $providerClass();
            $provider->register($this->container);
            $provider->boot($this->container);
        }
    }
}
