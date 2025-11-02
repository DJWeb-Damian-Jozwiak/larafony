<?php

namespace Larafony\Framework\ErrorHandler;

class TraceCollection
{
    /**
     * @var array<int, TraceFrame>
     */
    public private(set) array $frames;

    /**
     * @param array<int, TraceFrame> $frames
     */
    public function __construct(array $frames = [])
    {
        $this->frames = array_values($frames);
    }

    public static function fromThrowable(\Throwable $exception): self
    {
        $trace = $exception->getTrace();

        // Add the exception's location as the first frame
        array_unshift($trace, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'function' => '__construct',
            'args' => [],
        ]);

        return new self(array_map(static fn (array $frame) => TraceFrame::fromArray($frame), $trace));
    }

}