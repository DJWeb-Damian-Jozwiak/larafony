<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol\FrameHead;

use Larafony\Framework\WebSockets\Protocol\Frame;

abstract class BaseFrameHead
{
    public int $length {
        get => strlen($this->frame->payload);
    }

    public function __construct(
        protected Frame $frame,
    ) {
    }

    abstract public function encode(): string;

    /**
     * Creates the appropriate FrameHead instance based on payload length.
     */
    public static function for(Frame $frame): self
    {
        $length = strlen($frame->payload);

        return match (true) {
            $length <= 125 => new TinyFrameHead($frame),
            $length <= 65535 => new MediumFrameHead($frame),
            default => new LargeFrameHead($frame),
        };
    }

    /**
     * Encodes the first byte (FIN + opcode).
     */
    protected function encodeFirstByte(): int
    {
        return ($this->frame->fin ? 128 : 0) | $this->frame->opcode->value;
    }

    /**
     * Encodes the mask bit.
     */
    protected function maskBit(): int
    {
        return $this->frame->mask ? 128 : 0;
    }
}
