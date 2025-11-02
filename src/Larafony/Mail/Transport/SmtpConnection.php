<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport;

use Larafony\Framework\Http\Helpers\Stream\StreamWrapper;
use Larafony\Framework\Http\Stream;
use Larafony\Framework\Mail\Exceptions\TransportError;

/**
 * SMTP socket connection wrapper using Stream.
 */
final class SmtpConnection
{
    public bool $isConnected {
        get => ! $this->stream->eof();
    }

    private function __construct(
        private Stream $stream
    ) {
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

        $stream = new Stream(new StreamWrapper($resource));

        return new self($stream);
    }

    public function write(string $data): void
    {
        $this->stream->write($data);
    }

    public function readLine(int $length = 515): string
    {
        // Read until newline or max length
        $line = '';
        while (! $this->stream->eof() && strlen($line) < $length) {
            $char = $this->stream->read(1);
            $line .= $char;
            if ($char === "\n") {
                break;
            }
        }
        return $line;
    }

    public function close(): void
    {
        $this->stream->close();
    }
}
