<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport;

use Larafony\Framework\Mail\Exceptions\TransportError;

/**
 * SMTP socket connection wrapper.
 */
final class SmtpConnection
{

    public bool $isConnected {
        get => ! $this->closed && is_resource($this->socket) && ! feof($this->socket);
    }
    /** @var resource */
    private mixed $socket;

    private bool $closed = false;

    private function __construct(mixed $socket)
    {
        $this->socket = $socket;
    }

    public static function create(string $host, int $port, int $timeout = 30): self
    {
        $errno = 0;
        $errstr = '';

        $resource = fsockopen($host, $port, $errno, $errstr, $timeout);

        if ($resource === false) {
            throw new TransportError(
                "Could not connect to {$host}:{$port} - [{$errno}] {$errstr}"
            );
        }

        stream_set_timeout($resource, $timeout);

        return new self($resource);
    }

    public function write(string $data): void
    {
        if (! $this->isConnected) {
            throw new TransportError('Cannot write to closed connection');
        }

        fwrite($this->socket, $data);
    }

    /**
     * @param int<0, max>|null $length
     */
    public function readLine(?int $length = 515): string
    {
        if (! $this->isConnected) {
            return '';
        }

        $line = fgets($this->socket, $length);

        if ($line === false) {
            return '';
        }

        return $line;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }
}
