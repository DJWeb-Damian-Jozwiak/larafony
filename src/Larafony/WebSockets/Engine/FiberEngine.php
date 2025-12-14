<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Engine;

use Closure;
use Fiber;
use Larafony\Framework\WebSockets\Connection;
use Larafony\Framework\WebSockets\Contracts\EngineContract;
use RuntimeException;
use Socket;
use SplObjectStorage;
use Throwable;

final class FiberEngine implements EngineContract
{
    private ?Socket $serverSocket = null;

    private bool $running = false;

    /** @var SplObjectStorage<Socket, Connection> */
    private SplObjectStorage $sockets;

    /** @var SplObjectStorage<Connection, Fiber<mixed, mixed, mixed, mixed>> */
    private SplObjectStorage $fibers;

    private ?Closure $onConnection = null;

    private ?Closure $onData = null;

    private ?Closure $onClose = null;

    private ?Closure $onError = null;

    public function __construct()
    {
        $this->sockets = new SplObjectStorage();
        $this->fibers = new SplObjectStorage();
    }

    public function listen(string $host, int $port): void
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($socket === false) {
            throw new RuntimeException('Failed to create socket: ' . socket_strerror(socket_last_error()));
        }

        $this->serverSocket = $socket;

        socket_set_option($this->serverSocket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_nonblock($this->serverSocket);

        if (socket_bind($this->serverSocket, $host, $port) === false) {
            throw new RuntimeException(
                "Failed to bind to {$host}:{$port}: " . socket_strerror(socket_last_error($this->serverSocket))
            );
        }

        if (socket_listen($this->serverSocket) === false) {
            throw new RuntimeException(
                'Failed to listen: ' . socket_strerror(socket_last_error($this->serverSocket))
            );
        }
    }

    public function onConnection(Closure $handler): void
    {
        $this->onConnection = $handler;
    }

    public function onData(Closure $handler): void
    {
        $this->onData = $handler;
    }

    public function onClose(Closure $handler): void
    {
        $this->onClose = $handler;
    }

    public function onError(Closure $handler): void
    {
        $this->onError = $handler;
    }

    public function run(): void
    {
        if ($this->serverSocket === null) {
            throw new RuntimeException('Server not listening. Call listen() first.');
        }

        $this->running = true;

        while ($this->running) {
            $this->tick();
        }
    }

    public function stop(): void
    {
        $this->running = false;

        foreach ($this->sockets as $socket) {
            $connection = $this->sockets[$socket];
            $connection->close();
        }

        $this->sockets = new SplObjectStorage();
        $this->fibers = new SplObjectStorage();

        if ($this->serverSocket !== null) {
            socket_close($this->serverSocket);
            $this->serverSocket = null;
        }
    }

    private function tick(): void
    {
        if ($this->serverSocket === null) {
            return;
        }

        $read = [$this->serverSocket, ...$this->sockets];

        $write = null;
        $except = null;

        $changed = socket_select($read, $write, $except, 0, 100000);

        if ($changed === false || $changed === 0) {
            $this->processFibers();

            return;
        }

        foreach ($read as $socket) {
            if ($socket === $this->serverSocket) {
                $this->acceptConnection();
            } else {
                $this->handleSocketData($socket);
            }
        }

        $this->processFibers();
    }

    private function acceptConnection(): void
    {
        if ($this->serverSocket === null) {
            return;
        }

        $clientSocket = socket_accept($this->serverSocket);

        if ($clientSocket === false) {
            return;
        }

        socket_set_nonblock($clientSocket);

        $address = '';
        $port = 0;
        socket_getpeername($clientSocket, $address, $port);

        $connection = new Connection(
            id: $this->generateConnectionId(),
            socket: $clientSocket,
            remoteAddress: "{$address}:{$port}",
        );

        $this->sockets[$clientSocket] = $connection;

        if ($this->onConnection !== null) {
            $this->spawnFiber($connection, $this->onConnection, $connection);
        }
    }

    private function handleSocketData(Socket $socket): void
    {
        if (! $this->sockets->contains($socket)) {
            return;
        }

        $connection = $this->sockets[$socket];
        $data = socket_read($socket, 65535);

        if ($data === false || $data === '') {
            $this->handleDisconnection($socket, $connection);

            return;
        }

        if ($this->onData !== null) {
            $this->spawnFiber($connection, $this->onData, $connection, $data);
        }
    }

    private function handleDisconnection(Socket $socket, Connection $connection): void
    {
        $connection->markDisconnected();

        if ($this->onClose !== null) {
            $this->spawnFiber($connection, $this->onClose, $connection);
        }

        if ($this->fibers->contains($connection)) {
            $this->fibers->detach($connection);
        }

        $this->sockets->detach($socket);
        socket_close($socket);
    }

    private function spawnFiber(Connection $connection, Closure $callback, mixed ...$args): void
    {
        $fiber = new Fiber(function () use ($callback, $args, $connection): void {
            try {
                $callback(...$args);
            } catch (Throwable $e) {
                if ($this->onError !== null) {
                    ($this->onError)($connection, $e);
                }
            }
        });

        $this->fibers[$connection] = $fiber;

        try {
            $fiber->start();
        } catch (Throwable $e) {
            if ($this->onError !== null) {
                ($this->onError)($connection, $e);
            }
        }
    }

    private function processFibers(): void
    {
        foreach ($this->fibers as $connection) {
            $fiber = $this->fibers[$connection];

            if ($fiber->isTerminated()) {
                $this->fibers->detach($connection);

                continue;
            }

            if ($fiber->isSuspended()) {
                try {
                    $fiber->resume();
                } catch (Throwable $e) {
                    if ($this->onError !== null) {
                        ($this->onError)($connection, $e);
                    }
                    $this->fibers->detach($connection);
                }
            }
        }
    }

    private function generateConnectionId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
