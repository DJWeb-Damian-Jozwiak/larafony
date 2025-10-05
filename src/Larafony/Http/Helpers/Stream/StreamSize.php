<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Stream;

final class StreamSize
{
    public ?int $size {
        get {
            $data = fstat($this->stream->stream);
            return is_array($data) ? $data['size'] : null;
        }
    }

    public function __construct(private readonly StreamWrapper $stream)
    {
    }

    public function tell(): int
    {
        $size = ftell($this->stream->stream);
        return $size !== false ? $size : 0;
    }
}
