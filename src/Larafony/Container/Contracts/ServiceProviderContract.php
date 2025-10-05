<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Contracts;

interface ServiceProviderContract
{
    /**
     * @var array<int|string, class-string>
     */
    public array $providers { get; }
    /**
     * Register services in the given container.
     *
     * @param ContainerContract $container The container to register services in
     */
    public function register(ContainerContract $container): self;

    /**
     * bootstrapping services
     */
    public function boot(ContainerContract $container): void;
}
