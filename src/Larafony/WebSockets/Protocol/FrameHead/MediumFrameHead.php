<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol\FrameHead;

/**
 * Frame head for payloads from 126 to 65535 bytes.
 *
 * Uses 16-bit extended length encoding (126 marker + 2 bytes).
 */
final class MediumFrameHead extends BaseFrameHead
{
    private const LENGTH_MARKER = 126;

    public function encode(): string
    {
        return pack(
            'C*',
            $this->encodeFirstByte(),
            $this->maskBit() | self::LENGTH_MARKER,
            ($this->length >> 8) & 255,
            $this->length & 255,
        );
    }
}
