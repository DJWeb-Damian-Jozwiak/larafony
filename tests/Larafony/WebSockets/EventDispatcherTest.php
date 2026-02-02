<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets;

use Larafony\Framework\WebSockets\Contracts\ConnectionContract;
use Larafony\Framework\WebSockets\EventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventDispatcher::class)]
final class EventDispatcherTest extends TestCase
{
    public function testAddsListener(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('test', fn () => null);

        $this->assertTrue($dispatcher->hasListeners('test'));
    }

    public function testHasNoListenersInitially(): void
    {
        $dispatcher = new EventDispatcher();

        $this->assertFalse($dispatcher->hasListeners('test'));
    }

    public function testDispatchesEvent(): void
    {
        $dispatcher = new EventDispatcher();
        $called = false;
        $receivedData = null;

        $connection = $this->createStub(ConnectionContract::class);

        $dispatcher->addListener('test', function ($data) use (&$called, &$receivedData): void {
            $called = true;
            $receivedData = $data;
        });

        $dispatcher->dispatch('test', 'test-data', $connection);

        $this->assertTrue($called);
        $this->assertSame('test-data', $receivedData);
    }

    public function testDispatchesEventToMultipleListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $callCount = 0;

        $connection = $this->createStub(ConnectionContract::class);

        $dispatcher->addListener('test', function () use (&$callCount): void {
            $callCount++;
        });

        $dispatcher->addListener('test', function () use (&$callCount): void {
            $callCount++;
        });

        $dispatcher->dispatch('test', null, $connection);

        $this->assertSame(2, $callCount);
    }

    public function testDoesNotDispatchToUnrelatedListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $called = false;

        $connection = $this->createStub(ConnectionContract::class);

        $dispatcher->addListener('other', function () use (&$called): void {
            $called = true;
        });

        $dispatcher->dispatch('test', null, $connection);

        $this->assertFalse($called);
    }

    public function testRemovesListener(): void
    {
        $dispatcher = new EventDispatcher();
        $callback = fn () => null;

        $dispatcher->addListener('test', $callback);
        $this->assertTrue($dispatcher->hasListeners('test'));

        $dispatcher->removeListener('test', $callback);
        $this->assertFalse($dispatcher->hasListeners('test'));
    }

    public function testRemoveListenerDoesNothingForNonExistentEvent(): void
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->removeListener('nonexistent', fn () => null);

        $this->assertFalse($dispatcher->hasListeners('nonexistent'));
    }

    public function testPassesConnectionToListener(): void
    {
        $dispatcher = new EventDispatcher();
        $receivedConnection = null;

        $connection = $this->createStub(ConnectionContract::class);
        $connection->method('getId')->willReturn('test-id');

        $dispatcher->addListener('test', function ($data, $conn) use (&$receivedConnection): void {
            $receivedConnection = $conn;
        });

        $dispatcher->dispatch('test', null, $connection);

        $this->assertSame($connection, $receivedConnection);
    }

    public function testPassesDispatcherToListener(): void
    {
        $dispatcher = new EventDispatcher();
        $receivedDispatcher = null;

        $connection = $this->createStub(ConnectionContract::class);

        $dispatcher->addListener('test', function ($data, $conn, $disp) use (&$receivedDispatcher): void {
            $receivedDispatcher = $disp;
        });

        $dispatcher->dispatch('test', null, $connection);

        $this->assertSame($dispatcher, $receivedDispatcher);
    }
}
