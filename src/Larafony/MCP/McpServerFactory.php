<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\MCP\Contracts\McpServerFactoryContract;
use Larafony\Framework\MCP\SimpleCache\SimpleCacheAdapter;
use Mcp\Server;
use Mcp\Server\Session\SessionStoreInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

/**
 * Factory for creating MCP servers with Larafony integration.
 *
 * Integrates MCP SDK with Larafony's container, event dispatcher,
 * logger, and cache for seamless AI tool integration.
 */
final class McpServerFactory implements McpServerFactoryContract
{
    public function __construct(
        private readonly ContainerContract $container,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?CacheInterface $discoveryCache = null,
        private readonly ?SessionStoreInterface $sessionStore = null,
    ) {
    }

    public function create(
        string $name,
        string $version,
        ?string $instructions = null,
        ?string $discoveryPath = null,
    ): Server {
        $builder = Server::builder()
            ->setServerInfo($name, $version)
            ->setContainer($this->container)
            ->setLogger($this->logger ?? new NullLogger());

        if ($this->eventDispatcher !== null) {
            $builder->setEventDispatcher($this->eventDispatcher);
        }

        if ($instructions !== null) {
            $builder->setInstructions($instructions);
        }

        if ($discoveryPath !== null) {
            $builder->setDiscovery(
                basePath: $discoveryPath,
                scanDirs: ['src', '.'],
                cache: $this->resolveDiscoveryCache(),
            );
        }

        if ($this->sessionStore !== null) {
            $builder->setSession($this->sessionStore);
        }

        return $builder->build();
    }

    private function resolveDiscoveryCache(): ?CacheInterface
    {
        if ($this->discoveryCache !== null) {
            return $this->discoveryCache;
        }

        if ($this->container->has(Cache::class)) {
            return new SimpleCacheAdapter($this->container->get(Cache::class));
        }

        return null;
    }
}
