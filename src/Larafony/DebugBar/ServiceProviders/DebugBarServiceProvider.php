<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\ServiceProviders;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\DebugBar\DebugBar;
use Larafony\Framework\Events\ListenerDiscovery;
use Psr\EventDispatcher\ListenerProviderInterface;

class DebugBarServiceProvider extends ServiceProvider
{
    public function boot(ContainerContract $container): void
    {
        $config = $container->get(ConfigContract::class);
        if (! $config->get('debugbar.enabled', false)) {
            return;
        }
        $debugBar = new DebugBar();

        // Get collectors configuration
        $collectors = $config->get('debugbar.collectors', []);
        $collectorInstances = [];

        // Register collectors from config
        foreach ($collectors as $name => $collectorClass) {
            $collector = $container->get($collectorClass);
            $debugBar->addCollector($name, $collector);
            $collectorInstances[] = $collector;
        }
        $debugBar->enable();

        $container->set(DebugBar::class, $debugBar);

        // Register event listeners using discovery
        $listenerProvider = $container->get(ListenerProviderInterface::class);
        $discovery = new ListenerDiscovery($listenerProvider, $collectorInstances);
        $discovery->discover();
    }
}
