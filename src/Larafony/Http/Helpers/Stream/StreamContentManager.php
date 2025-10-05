<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Stream;

final class StreamContentManager
{
    public string $content {
        get {
            $content = stream_get_contents($this->stream->stream, -1, 0);
            if (! $content) {
                return '';
            }
            return $content;
        }
    }

    public function __construct(private readonly StreamWrapper $stream)
    {
    }

    public function read(int $length): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('length must be positive integer');
        }
        $data = fread($this->stream->stream, $length);
        return $data ? $data : '';
    }

    public function write(string $string): int
    {
        $size = fwrite($this->stream->stream, $string);
        return $size ? $size : 0;
    }
}
