<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol\FrameHead;

use Larafony\Framework\WebSockets\Protocol\Frame;
use Larafony\Framework\WebSockets\Protocol\FrameHead\LargeFrameHead;
use PHPUnit\Framework\TestCase;

final class LargeFrameHeadTest extends TestCase
{
    public function testEncodesTenByteHeader(): void
    {
        $frame = Frame::text(str_repeat('x', 65536));
        $frameHead = new LargeFrameHead($frame);

        $encoded = $frameHead->encode();

        // 2 base bytes + 8 extended length bytes = 10
        $this->assertSame(10, strlen($encoded));
    }

    public function testEncodesLengthMarker127(): void
    {
        $frame = Frame::text(str_repeat('x', 65536));
        $frameHead = new LargeFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        // Second byte: length marker 127
        $this->assertSame(127, $bytes[2]);
    }

    public function testEncodesExtendedLength64Bit(): void
    {
        $length = 65536;
        $frame = Frame::text(str_repeat('x', $length));
        $frameHead = new LargeFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        // 65536 = 0x00010000 in 64-bit = 0x0000000000010000
        // Bytes 3-10 are the 64-bit length
        $this->assertSame(0, $bytes[3]);
        $this->assertSame(0, $bytes[4]);
        $this->assertSame(0, $bytes[5]);
        $this->assertSame(0, $bytes[6]);
        $this->assertSame(0, $bytes[7]);
        $this->assertSame(1, $bytes[8]);   // 0x01
        $this->assertSame(0, $bytes[9]);   // 0x00
        $this->assertSame(0, $bytes[10]);  // 0x00
    }

    public function testEncodesWithMaskBit(): void
    {
        $frame = Frame::text(str_repeat('x', 65536), mask: true);
        $frameHead = new LargeFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        // Second byte: mask bit (128) + marker (127) = 255
        $this->assertSame(255, $bytes[2]);
    }

    public function testEncodesFirstByteCorrectly(): void
    {
        $frame = Frame::text(str_repeat('x', 65536));
        $frameHead = new LargeFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        // First byte: FIN(1) + opcode(1 for text) = 0x81
        $this->assertSame(0x81, $bytes[1]);
    }
}
