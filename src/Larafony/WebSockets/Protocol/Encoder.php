<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol;

use Larafony\Framework\WebSockets\Protocol\FrameHead\BaseFrameHead;

final class Encoder
{
    /**
     * Encodes a WebSocket frame according to RFC 6455 section 5.2.
     *
     * @link https://tools.ietf.org/html/rfc6455#section-5.2
     */
    public static function encode(Frame $frame): string
    {
        $frameHead = BaseFrameHead::for($frame);
        $encodedFrame = $frameHead->encode();

        if ($frame->mask && $frame->maskingKey !== null) {
            $encodedFrame .= $frame->maskingKey;
            $encodedFrame .= self::applyMask($frame->payload, $frame->maskingKey);
        } else {
            $encodedFrame .= $frame->payload;
        }

        return $encodedFrame;
    }

    public static function applyMask(string $payload, string $maskingKey): string
    {
        $masked = '';
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $masked .= $payload[$i] ^ $maskingKey[$i % 4];
        }

        return $masked;
    }
}
