<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets;

use Larafony\Framework\WebSockets\Connection;
use Larafony\Framework\WebSockets\Protocol\Frame;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Socket;

#[CoversClass(Connection::class)]
final class ConnectionTest extends TestCase
{
    private ?Socket $socket = null;

    protected function setUp(): void
    {
        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    }

    protected function tearDown(): void
    {
        if ($this->socket !== null && $this->socket !== false) {
            @socket_close($this->socket);
        }
    }

    public function testReturnsId(): void
    {
        if ($this->socket === false) {
            $this->markTestSkipped('Cannot create socket');
        }

        $connection = new Connection('test-id', $this->socket, '127.0.0.1:8080');

        $this->assertSame('test-id', $connection->getId());
    }

    public function testReturnsRemoteAddress(): void
    {
        if ($this->socket === false) {
            $this->markTestSkipped('Cannot create socket');
        }

        $connection = new Connection('test-id', $this->socket, '192.168.1.1:12345');

        $this->assertSame('192.168.1.1:12345', $connection->getRemoteAddress());
    }

    public function testIsConnectedInitially(): void
    {
        if ($this->socket === false) {
            $this->markTestSkipped('Cannot create socket');
        }

        $connection = new Connection('test-id', $this->socket, '127.0.0.1:8080');

        $this->assertTrue($connection->isConnected());
    }

    public function testMarkDisconnectedChangesState(): void
    {
        if ($this->socket === false) {
            $this->markTestSkipped('Cannot create socket');
        }

        $connection = new Connection('test-id', $this->socket, '127.0.0.1:8080');
        $connection->markDisconnected();

        $this->assertFalse($connection->isConnected());
    }

    public function testReturnsSocket(): void
    {
        if ($this->socket === false) {
            $this->markTestSkipped('Cannot create socket');
        }

        $connection = new Connection('test-id', $this->socket, '127.0.0.1:8080');

        $this->assertSame($this->socket, $connection->getSocket());
    }

    public function testSendDoesNothingWhenDisconnected(): void
    {
        if ($this->socket === false) {
            $this->markTestSkipped('Cannot create socket');
        }

        $connection = new Connection('test-id', $this->socket, '127.0.0.1:8080');
        $connection->markDisconnected();

        $connection->send('test');

        $this->assertFalse($connection->isConnected());
    }

    public function testCloseDoesNothingWhenAlreadyDisconnected(): void
    {
        if ($this->socket === false) {
            $this->markTestSkipped('Cannot create socket');
        }

        $connection = new Connection('test-id', $this->socket, '127.0.0.1:8080');
        $connection->markDisconnected();

        $connection->close();

        $this->assertFalse($connection->isConnected());
    }

    public function testSendAcceptsFrame(): void
    {
        if ($this->socket === false) {
            $this->markTestSkipped('Cannot create socket');
        }

        $connection = new Connection('test-id', $this->socket, '127.0.0.1:8080');
        $frame = Frame::text('test');

        $connection->send($frame);

        $this->assertTrue($connection->isConnected());
    }
}
