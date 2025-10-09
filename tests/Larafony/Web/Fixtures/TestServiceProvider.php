<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Web\Fixtures;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\Contracts\ServiceProviderContract;
use Larafony\Framework\Container\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public bool $registered = false;
    public bool $booted = false;

    public array $providers {
        get => [];
    }

    public function register(ContainerContract $container): self
    {
        $this->registered = true;
        $container->bind('test_service', 'test_value');
        return $this;
    }

    public function boot(ContainerContract $container): void
    {
        $this->booted = true;
    }
}
