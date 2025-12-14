<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\ServiceProviders;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\WebSockets\Contracts\EngineContract;
use Larafony\Framework\WebSockets\Contracts\ServerContract;
use Larafony\Framework\WebSockets\Engine\FiberEngine;
use Larafony\Framework\WebSockets\Server;

class WebSocketServiceProvider extends ServiceProvider
{
    /**
     * @return array<string, class-string>
     */
    public function providers(): array
    {
        return [
            EngineContract::class => FiberEngine::class,
        ];
    }

    public function boot(ContainerContract $container): void
    {
        parent::boot($container);

        $config = $container->get(ConfigContract::class);

        $host = $config->get('websocket.host', '0.0.0.0');
        $port = (int) $config->get('websocket.port', 8080);

        $engine = $container->get(EngineContract::class);
        $server = new Server($engine, $host, $port);

        $this->registerDefaultHandlers($server);

        $container->set(ServerContract::class, $server);
        $container->set(Server::class, $server);
    }

    protected function registerDefaultHandlers(ServerContract $server): void
    {
    }
}
