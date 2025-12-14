<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol\FrameHead;

/**
 * Frame head for payloads up to 125 bytes.
 *
 * Uses 7-bit length encoding (no extended length bytes).
 */
final class TinyFrameHead extends BaseFrameHead
{
    public function encode(): string
    {
        return pack(
            'C*',
            $this->encodeFirstByte(),
            $this->maskBit() | $this->length,
        );
    }
}
