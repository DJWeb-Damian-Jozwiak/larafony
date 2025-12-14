<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol;

use Larafony\Framework\WebSockets\Protocol\Asserts\AssertMinimumFrameLength;
use Larafony\Framework\WebSockets\Protocol\Asserts\AssertSufficientBytes;
use Larafony\Framework\WebSockets\Protocol\Asserts\AssertUnpackSuccess;

final class Decoder
{
    /**
     * Decodes a WebSocket frame according to RFC 6455 section 5.2.
     *
     * @link https://tools.ietf.org/html/rfc6455#section-5.2
     */
    public static function decode(string $data): Frame
    {
        AssertMinimumFrameLength::assert($data);

        $bytes = array_values(AssertUnpackSuccess::assert(unpack('C*', $data)));

        $firstByte = array_shift($bytes);
        $fin = (bool) ($firstByte & 128);
        $opcode = Opcode::from($firstByte & 15);

        $secondByte = array_shift($bytes);
        $mask = (bool) ($secondByte & 128);
        $payloadLength = $secondByte & 127;

        [$payloadLength, $bytes] = self::extractBytes($payloadLength, $bytes);

        $maskingKey = null;
        if ($mask) {
            AssertSufficientBytes::forMaskingKey($bytes);
            $maskingKey = pack('C*', ...array_slice($bytes, 0, 4));
            $bytes = array_slice($bytes, 4);
        }

        $payload = $bytes !== [] ? pack('C*', ...$bytes) : '';

        if ($maskingKey !== null) {
            $payload = Encoder::applyMask($payload, $maskingKey);
        }

        return new Frame($fin, $opcode, $mask, $payloadLength, $maskingKey, $payload);
    }

    /**
     * @param int $payloadLength
     * @param array<int, mixed> $bytes
     *
     * @return array<int, mixed>
     */
    public static function extractBytes(int $payloadLength, array $bytes): array
    {
        if ($payloadLength === 126) {
            AssertSufficientBytes::forExtendedLength16($bytes);
            $payloadLength = ($bytes[0] << 8) | $bytes[1];
            $bytes = array_slice($bytes, 2);
        } elseif ($payloadLength === 127) {
            AssertSufficientBytes::forExtendedLength64($bytes);
            $payloadLength = 0;
            for ($i = 0; $i < 8; $i++) {
                $payloadLength = ($payloadLength << 8) | $bytes[$i];
            }
            $bytes = array_slice($bytes, 8);
        }
        return [$payloadLength, $bytes];
    }
}
