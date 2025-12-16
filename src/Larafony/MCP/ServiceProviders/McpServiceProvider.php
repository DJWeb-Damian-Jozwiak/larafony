<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP\ServiceProviders;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\Console\CommandDiscovery;
use Larafony\Framework\Console\CommandRegistry;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\MCP\Contracts\McpServerFactoryContract;
use Larafony\Framework\MCP\McpServerFactory;
use Larafony\Framework\MCP\Session\CacheSessionStore;
use Larafony\Framework\MCP\SimpleCache\SimpleCacheAdapter;
use Mcp\Server\Session\SessionStoreInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Service provider for MCP (Model Context Protocol) integration.
 *
 * Registers MCP server factory and session store, enabling
 * AI assistants to interact with Larafony applications.
 */
class McpServiceProvider extends ServiceProvider
{
    /**
     * @return array<string, class-string>
     */
    public function providers(): array
    {
        return [
            McpServerFactoryContract::class => McpServerFactory::class,
        ];
    }

    public function boot(ContainerContract $container): void
    {
        parent::boot($container);

        $this->registerConsoleCommands($container);
        $this->registerSimpleCacheAdapter($container);
        $this->registerSessionStore($container);
        $this->registerServerFactory($container);
    }

    private function registerConsoleCommands(ContainerContract $container): void
    {
        if (! $container->has(CommandRegistry::class)) {
            return;
        }

        $registry = $container->get(CommandRegistry::class);
        $discovery = new CommandDiscovery();
        $commandsDir = __DIR__ . '/../Console';
        $discovery->discover($commandsDir, 'Larafony\\Framework\\MCP\\Console');

        foreach ($discovery->commands as $name => $class) {
            $registry->register($name, $class);
        }
    }

    private function registerSimpleCacheAdapter(ContainerContract $container): void
    {
        if ($container->has(Cache::class)) {
            $cache = $container->get(Cache::class);
            $adapter = new SimpleCacheAdapter($cache);
            $container->set(CacheInterface::class, $adapter);
        }
    }

    private function registerSessionStore(ContainerContract $container): void
    {
        if ($container->has(Cache::class)) {
            $cache = $container->get(Cache::class);
            $sessionStore = new CacheSessionStore($cache);
            $container->set(SessionStoreInterface::class, $sessionStore);
        }
    }

    private function registerServerFactory(ContainerContract $container): void
    {
        $eventDispatcher = $container->has(EventDispatcherInterface::class)
            ? $container->get(EventDispatcherInterface::class)
            : null;

        $logger = $container->has(LoggerInterface::class)
            ? $container->get(LoggerInterface::class)
            : null;

        $discoveryCache = $container->has(CacheInterface::class)
            ? $container->get(CacheInterface::class)
            : null;

        $sessionStore = $container->has(SessionStoreInterface::class)
            ? $container->get(SessionStoreInterface::class)
            : null;

        $factory = new McpServerFactory(
            container: $container,
            eventDispatcher: $eventDispatcher,
            logger: $logger,
            discoveryCache: $discoveryCache,
            sessionStore: $sessionStore,
        );

        $container->set(McpServerFactoryContract::class, $factory);
        $container->set(McpServerFactory::class, $factory);
    }
}
