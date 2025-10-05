<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Deferred;

use Larafony\Framework\Container\Attributes\Deferred;
use ReflectionClass;

/**
 * Manages registration and tracking of deferred service providers.
 */
final class ProviderRegistry
{
    /**
     * @var array<class-string, class-string> Map of service => provider class
     */
    private array $deferredServices = [];

    /**
     * @var array<class-string> List of already loaded providers
     */
    private array $loadedProviders = [];

    /**
     * Register a deferred provider by analyzing its #[Deferred] attribute.
     *
     * @param class-string $providerClass
     */
    public function register(string $providerClass): void
    {
        $reflection = new ReflectionClass($providerClass);
        $attributes = $reflection->getAttributes(Deferred::class);

        /** @var Deferred $deferred */
        $deferred = $attributes[0]?->newInstance() ?? new Deferred();

        foreach ($deferred->provides as $service) {
            $this->deferredServices[$service] = $providerClass;
        }
    }

    /**
     * Get provider class for a service.
     */
    public function getProviderFor(string $service): ?string
    {
        return $this->deferredServices[$service] ?? null;
    }

    /**
     * Check if provider has been loaded.
     */
    public function isLoaded(string $providerClass): bool
    {
        return in_array($providerClass, $this->loadedProviders, true);
    }

    /**
     * Mark provider as loaded.
     */
    public function markAsLoaded(string $providerClass): void
    {
        if (! $this->isLoaded($providerClass)) {
            $this->loadedProviders[] = $providerClass;
        }
    }

    /**
     * Get all registered deferred services.
     *
     * @return array<class-string>
     */
    public function getServices(): array
    {
        return array_keys($this->deferredServices);
    }
}
