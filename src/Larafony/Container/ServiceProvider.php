<?php

declare(strict_types=1);

namespace Larafony\Framework\Container;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\Contracts\ServiceProviderContract;

abstract class ServiceProvider implements ServiceProviderContract
{
    public function register(ContainerContract $container): self
    {
        foreach ($this->providers as $key => $class) {
            if (is_int($key)) {
                $container->set($class, $class);
            } else {
                $container->set($key, $class);
            }
        }
        return $this;
    }
    public function boot(ContainerContract $container): void
    {
    }
}
