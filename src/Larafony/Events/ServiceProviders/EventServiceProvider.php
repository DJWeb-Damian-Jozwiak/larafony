<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\ServiceProviders;

use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Events\EventDispatcher;
use Larafony\Framework\Events\ListenerProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventServiceProvider extends ServiceProvider
{
    public function boot(\Larafony\Framework\Container\Contracts\ContainerContract $container): void
    {
        $container->set(
            ListenerProviderInterface::class,
            new ListenerProvider($container)
        );

        $container->set(
            EventDispatcherInterface::class,
            new EventDispatcher($container->get(ListenerProviderInterface::class))
        );
    }
}
