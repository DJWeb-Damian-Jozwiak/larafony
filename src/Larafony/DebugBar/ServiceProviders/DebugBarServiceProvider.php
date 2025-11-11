<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\ServiceProviders;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\DebugBar\Collectors\CacheCollector;
use Larafony\Framework\DebugBar\Collectors\PerformanceCollector;
use Larafony\Framework\DebugBar\Collectors\QueryCollector;
use Larafony\Framework\DebugBar\Collectors\RequestCollector;
use Larafony\Framework\DebugBar\Collectors\RouteCollector;
use Larafony\Framework\DebugBar\Collectors\ViewCollector;
use Larafony\Framework\DebugBar\DebugBar;
use Larafony\Framework\Events\ListenerDiscovery;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Message\ServerRequestInterface;

class DebugBarServiceProvider extends ServiceProvider
{

    public function boot(ContainerContract $container): void
    {
        // Create singleton DebugBar instance
        $debugBar = new DebugBar();


        // Create collector instances
        $queryCollector = $container->get(QueryCollector::class);
        $cacheCollector = $container->get(CacheCollector::class);
        $viewCollector = $container->get(ViewCollector::class);
        $routeCollector = $container->get(RouteCollector::class);
        $performanceCollector = $container->get(PerformanceCollector::class);

        // Add collectors to debugbar
        $debugBar->addCollector('queries', $queryCollector);
        $debugBar->addCollector('cache', $cacheCollector);
        $debugBar->addCollector('views', $viewCollector);
        $debugBar->addCollector('route', $routeCollector);
        $debugBar->addCollector('performance', $performanceCollector);

        // Add request collector if request is available
        if ($container->has(ServerRequestInterface::class)) {
            $requestCollector = new RequestCollector($container->get(ServerRequestInterface::class));
            $debugBar->addCollector('request', $requestCollector);
        }

        $container->set(DebugBar::class, $debugBar);

        // Register event listeners using discovery with instances
        $listenerProvider = $container->get(ListenerProviderInterface::class);
        $discovery = new ListenerDiscovery($listenerProvider, [
            $queryCollector,
            $cacheCollector,
            $viewCollector,
            $routeCollector,
        ]);
        $discovery->discover();
    }
}
