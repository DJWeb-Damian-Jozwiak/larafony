<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets;

use Closure;
use Larafony\Framework\WebSockets\Contracts\ConnectionContract;
use Larafony\Framework\WebSockets\Contracts\EngineContract;
use Larafony\Framework\WebSockets\Contracts\ServerContract;
use Larafony\Framework\WebSockets\Protocol\Decoder;
use Larafony\Framework\WebSockets\Protocol\Frame;
use Larafony\Framework\WebSockets\Protocol\Handshake;
use Larafony\Framework\WebSockets\Protocol\Opcode;
use SplObjectStorage;
use Throwable;

final class Server implements ServerContract
{
    /** @var SplObjectStorage<ConnectionContract, bool> */
    private SplObjectStorage $connections;

    /** @var SplObjectStorage<ConnectionContract, string> */
    private SplObjectStorage $buffers;

    /** @var SplObjectStorage<ConnectionContract, bool> */
    private SplObjectStorage $handshakeCompleted;

    private EventDispatcher $eventDispatcher;

    public function __construct(
        private readonly EngineContract $engine,
        private readonly string $host = '0.0.0.0',
        private readonly int $port = 8080,
    ) {
        $this->connections = new SplObjectStorage();
        $this->buffers = new SplObjectStorage();
        $this->handshakeCompleted = new SplObjectStorage();
        $this->eventDispatcher = new EventDispatcher();

        $this->setupEngineHandlers();
    }

    public function on(string $event, callable $callback): void
    {
        $this->eventDispatcher->addListener($event, $callback);
    }

    public function broadcast(string $message, ?Closure $filter = null): void
    {
        foreach ($this->connections as $connection) {
            if ($filter !== null && ! $filter($connection)) {
                continue;
            }

            if ($connection->isConnected()) {
                $connection->send($message);
            }
        }
    }

    /**
     * @return SplObjectStorage<ConnectionContract, bool>
     */
    public function getConnections(): SplObjectStorage
    {
        return $this->connections;
    }

    public function run(): void
    {
        $this->engine->listen($this->host, $this->port);
        $this->engine->run();
    }

    public function stop(): void
    {
        $this->engine->stop();
    }

    private function setupEngineHandlers(): void
    {
        $this->engine->onConnection(function (ConnectionContract $connection): void {
            $this->buffers[$connection] = '';
            $this->handshakeCompleted[$connection] = false;
        });

        $this->engine->onData(function (ConnectionContract $connection, string $data): void {
            $this->handleData($connection, $data);
        });

        $this->engine->onClose(function (ConnectionContract $connection): void {
            $this->handleClose($connection);
        });

        $this->engine->onError(function (ConnectionContract $connection, Throwable $e): void {
            $this->eventDispatcher->dispatch('error', $e, $connection);
        });
    }

    private function handleData(ConnectionContract $connection, string $data): void
    {
        if (! $this->handshakeCompleted->contains($connection) || ! $this->handshakeCompleted[$connection]) {
            $this->handleHandshake($connection, $data);

            return;
        }

        $this->handleWebSocketFrame($connection, $data);
    }

    private function handleHandshake(ConnectionContract $connection, string $data): void
    {
        $this->buffers[$connection] .= $data;
        $buffer = $this->buffers[$connection];

        $headers = Handshake::parseRequest($buffer);

        if ($headers === null) {
            return;
        }

        if (! Handshake::isValidUpgradeRequest($headers)) {
            $connection->send(Frame::text(Handshake::createErrorResponse(400, 'Bad Request')));
            $connection->close();

            return;
        }

        $response = Handshake::createResponse($headers['Sec-WebSocket-Key']);

        if ($connection instanceof Connection) {
            $socket = $connection->getSocket();
            socket_write($socket, $response, strlen($response));
        }

        $this->handshakeCompleted[$connection] = true;
        $this->connections[$connection] = true;
        $this->buffers[$connection] = '';

        $this->eventDispatcher->dispatch('open', null, $connection);
    }

    private function handleWebSocketFrame(ConnectionContract $connection, string $data): void
    {
        try {
            $frame = Decoder::decode($data);

            match ($frame->opcode) {
                Opcode::TEXT, Opcode::BINARY => $this->handleMessage($connection, $frame),
                Opcode::PING => $this->handlePing($connection, $frame),
                Opcode::PONG => $this->handlePong($connection, $frame),
                Opcode::CLOSE => $this->handleCloseFrame($connection, $frame),
                default => null,
            };
        } catch (Throwable $e) {
            $this->eventDispatcher->dispatch('error', $e, $connection);
        }
    }

    private function handleMessage(ConnectionContract $connection, Frame $frame): void
    {
        $payload = $frame->payload;

        $decoded = json_decode($payload, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['event'])) {
            $this->eventDispatcher->dispatch(
                $decoded['event'],
                $decoded['data'] ?? null,
                $connection
            );

            return;
        }

        $this->eventDispatcher->dispatch('message', $payload, $connection);
    }

    private function handlePing(ConnectionContract $connection, Frame $frame): void
    {
        $connection->send(Frame::pong($frame->payload));
    }

    private function handlePong(ConnectionContract $connection, Frame $frame): void
    {
        $this->eventDispatcher->dispatch('pong', $frame->payload, $connection);
    }

    private function handleCloseFrame(ConnectionContract $connection, Frame $frame): void
    {
        $code = 1000;
        $reason = '';

        if (strlen($frame->payload) >= 2) {
            $unpacked = unpack('n', substr($frame->payload, 0, 2));
            $code = $unpacked !== false ? $unpacked[1] : 1000;
            $reason = substr($frame->payload, 2);
        }

        $connection->close($code, $reason);
        $this->handleClose($connection);
    }

    private function handleClose(ConnectionContract $connection): void
    {
        $this->eventDispatcher->dispatch('close', null, $connection);

        if ($this->connections->contains($connection)) {
            $this->connections->detach($connection);
        }

        if ($this->buffers->contains($connection)) {
            $this->buffers->detach($connection);
        }

        if ($this->handshakeCompleted->contains($connection)) {
            $this->handshakeCompleted->detach($connection);
        }
    }
}
