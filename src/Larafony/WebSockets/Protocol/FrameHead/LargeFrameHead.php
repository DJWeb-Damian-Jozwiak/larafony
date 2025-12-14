<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol\FrameHead;

/**
 * Frame head for payloads larger than 65535 bytes.
 *
 * Uses 64-bit extended length encoding (127 marker + 8 bytes).
 */
final class LargeFrameHead extends BaseFrameHead
{
    private const int LENGTH_MARKER = 127;

    public function encode(): string
    {
        $bytes = [
            $this->encodeFirstByte(),
            $this->maskBit() | self::LENGTH_MARKER,
        ];

        for ($i = 0; $i < 8; $i++) {
            $bytes[] = ($this->length >> (7 - $i) * 8) & 255;
        }

        return pack('C*', ...$bytes);
    }
}
