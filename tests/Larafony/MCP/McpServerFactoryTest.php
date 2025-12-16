<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\MCP;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\MCP\Contracts\McpServerFactoryContract;
use Larafony\Framework\MCP\McpServerFactory;
use Mcp\Server;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

final class McpServerFactoryTest extends TestCase
{
    public function testImplementsContract(): void
    {
        $container = $this->createMock(ContainerContract::class);
        $factory = new McpServerFactory($container);

        $this->assertInstanceOf(McpServerFactoryContract::class, $factory);
    }

    public function testCreateReturnsServer(): void
    {
        $container = $this->createMock(ContainerContract::class);
        $factory = new McpServerFactory($container);

        $server = $factory->create('Test Server', '1.0.0');

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testCreateWithLogger(): void
    {
        $container = $this->createMock(ContainerContract::class);
        $logger = $this->createMock(LoggerInterface::class);

        $factory = new McpServerFactory(
            container: $container,
            logger: $logger,
        );

        $server = $factory->create('Test Server', '1.0.0');

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testCreateWithEventDispatcher(): void
    {
        $container = $this->createMock(ContainerContract::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $factory = new McpServerFactory(
            container: $container,
            eventDispatcher: $eventDispatcher,
        );

        $server = $factory->create('Test Server', '1.0.0');

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testCreateWithInstructions(): void
    {
        $container = $this->createMock(ContainerContract::class);
        $factory = new McpServerFactory($container);

        $server = $factory->create(
            name: 'Test Server',
            version: '1.0.0',
            instructions: 'Use these tools for testing',
        );

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testCreateWithDiscoveryPath(): void
    {
        $container = $this->createMock(ContainerContract::class);
        $container->method('has')->willReturn(false);

        $factory = new McpServerFactory($container);

        $server = $factory->create(
            name: 'Test Server',
            version: '1.0.0',
            discoveryPath: __DIR__,
        );

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testCreateWithDiscoveryCacheFallsBackToContainer(): void
    {
        $container = $this->createMock(ContainerContract::class);
        $cache = $this->createMock(CacheInterface::class);

        $factory = new McpServerFactory(
            container: $container,
            discoveryCache: $cache,
        );

        $server = $factory->create(
            name: 'Test Server',
            version: '1.0.0',
            discoveryPath: __DIR__,
        );

        $this->assertInstanceOf(Server::class, $server);
    }
}
