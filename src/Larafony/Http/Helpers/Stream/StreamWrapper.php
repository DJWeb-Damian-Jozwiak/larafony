<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Stream;

use InvalidArgumentException;

class StreamWrapper
{
    /**
     * @var resource $stream
     */
    public private(set) mixed $stream {
        get => is_resource($this->stream) ?
            $this->stream : throw new InvalidArgumentException('Stream closed');
        set {
            if (! is_resource($value)) {
                throw new InvalidArgumentException('stream must be a resource');
            }
            $this->stream = $value;
        }
    }

    public private(set) bool $closed = false;

    public function __construct(mixed $stream)
    {
        $this->stream = $stream;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        fclose($this->stream);
    }

    public function rewind(): void
    {
        rewind($this->stream);
    }

    public function eof(): bool
    {
        return feof($this->stream);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        fseek($this->stream, $offset, $whence);
    }
}
