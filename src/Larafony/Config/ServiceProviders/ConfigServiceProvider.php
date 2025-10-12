<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\ServiceProviders;

use Larafony\Framework\Config\ConfigBase;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Config\Environment\EnvironmentLoader;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * @return  array<int|string, class-string> $providers
     */
    public function providers(): array
    {
        return [
            EnvironmentLoader::class => EnvironmentLoader::class,
            ConfigBase::class => ConfigBase::class,
            ConfigContract::class => ConfigBase::class,
        ];
    }

    public function boot(ContainerContract $container): void
    {
        parent::boot($container);
        $container->get(ConfigContract::class)->loadConfig();
    }
}
