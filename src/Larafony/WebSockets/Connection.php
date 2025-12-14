<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets;

use Larafony\Framework\WebSockets\Contracts\ConnectionContract;
use Larafony\Framework\WebSockets\Protocol\Encoder;
use Larafony\Framework\WebSockets\Protocol\Frame;
use Socket;

final class Connection implements ConnectionContract
{
    private bool $connected = true;

    public function __construct(
        private readonly string $id,
        private readonly Socket $socket,
        private readonly string $remoteAddress,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function send(string|Frame $data): void
    {
        if (! $this->connected) {
            return;
        }

        $frame = $data instanceof Frame
            ? $data
            : Frame::text($data);

        $encoded = Encoder::encode($frame);

        socket_write($this->socket, $encoded, strlen($encoded));
    }

    public function close(int $code = 1000, string $reason = ''): void
    {
        if (! $this->connected) {
            return;
        }

        $frame = Frame::close($code, $reason);
        $encoded = Encoder::encode($frame);

        socket_write($this->socket, $encoded, strlen($encoded));
        socket_close($this->socket);

        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function getRemoteAddress(): string
    {
        return $this->remoteAddress;
    }

    public function getSocket(): Socket
    {
        return $this->socket;
    }

    public function markDisconnected(): void
    {
        $this->connected = false;
    }
}
