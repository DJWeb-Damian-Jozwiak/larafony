<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets;

use Closure;
use Larafony\Framework\WebSockets\Contracts\EngineContract;
use Larafony\Framework\WebSockets\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Server::class)]
final class ServerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $engine = $this->createMock(EngineContract::class);
        $engine->expects($this->once())->method('onConnection');
        $engine->expects($this->once())->method('onData');
        $engine->expects($this->once())->method('onClose');
        $engine->expects($this->once())->method('onError');

        $server = new Server($engine);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testRegistersEventListener(): void
    {
        $engine = $this->createStub(EngineContract::class);
        $server = new Server($engine);

        $server->on('message', fn () => null);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testRunDelegatestoEngine(): void
    {
        $engine = $this->createMock(EngineContract::class);
        $engine->expects($this->once())
            ->method('listen')
            ->with('127.0.0.1', 9000);
        $engine->expects($this->once())->method('run');

        $server = new Server($engine, '127.0.0.1', 9000);
        $server->run();
    }

    public function testStopDelegatesToEngine(): void
    {
        $engine = $this->createMock(EngineContract::class);
        $engine->expects($this->once())->method('stop');

        $server = new Server($engine);
        $server->stop();
    }

    public function testGetConnectionsReturnsStorage(): void
    {
        $engine = $this->createStub(EngineContract::class);
        $server = new Server($engine);

        $connections = $server->getConnections();

        $this->assertCount(0, $connections);
    }

    public function testBroadcastWithNoConnections(): void
    {
        $engine = $this->createStub(EngineContract::class);
        $server = new Server($engine);

        $server->broadcast('test message');

        $this->assertCount(0, $server->getConnections());
    }

    public function testBroadcastWithFilter(): void
    {
        $engine = $this->createStub(EngineContract::class);
        $server = new Server($engine);

        $server->broadcast('test', fn () => false);

        $this->assertCount(0, $server->getConnections());
    }

    public function testSetsUpEngineHandlers(): void
    {
        $handlers = [];

        $engine = $this->createStub(EngineContract::class);
        $engine->method('onConnection')->willReturnCallback(function (Closure $handler) use (&$handlers): void {
            $handlers['connection'] = $handler;
        });
        $engine->method('onData')->willReturnCallback(function (Closure $handler) use (&$handlers): void {
            $handlers['data'] = $handler;
        });
        $engine->method('onClose')->willReturnCallback(function (Closure $handler) use (&$handlers): void {
            $handlers['close'] = $handler;
        });
        $engine->method('onError')->willReturnCallback(function (Closure $handler) use (&$handlers): void {
            $handlers['error'] = $handler;
        });

        new Server($engine);

        $this->assertArrayHasKey('connection', $handlers);
        $this->assertArrayHasKey('data', $handlers);
        $this->assertArrayHasKey('close', $handlers);
        $this->assertArrayHasKey('error', $handlers);
    }
}
