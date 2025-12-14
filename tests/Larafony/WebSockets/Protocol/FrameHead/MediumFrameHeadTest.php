<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol\FrameHead;

use Larafony\Framework\WebSockets\Protocol\Frame;
use Larafony\Framework\WebSockets\Protocol\FrameHead\MediumFrameHead;
use PHPUnit\Framework\TestCase;

final class MediumFrameHeadTest extends TestCase
{
    public function testEncodesFourByteHeader(): void
    {
        $frame = Frame::text(str_repeat('x', 126));
        $frameHead = new MediumFrameHead($frame);

        $encoded = $frameHead->encode();

        $this->assertSame(4, strlen($encoded));
    }

    public function testEncodesLengthMarker126(): void
    {
        $frame = Frame::text(str_repeat('x', 200));
        $frameHead = new MediumFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        // Second byte: length marker 126
        $this->assertSame(126, $bytes[2]);
    }

    public function testEncodesExtendedLength16Bit(): void
    {
        $frame = Frame::text(str_repeat('x', 300));
        $frameHead = new MediumFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        // Extended length: 300 = 0x012C
        $this->assertSame(1, $bytes[3]);   // High byte
        $this->assertSame(44, $bytes[4]);  // Low byte (0x2C = 44)
    }

    public function testEncodesMaxMediumLength(): void
    {
        $frame = Frame::text(str_repeat('x', 65535));
        $frameHead = new MediumFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        // Extended length: 65535 = 0xFFFF
        $this->assertSame(255, $bytes[3]);
        $this->assertSame(255, $bytes[4]);
    }

    public function testEncodesWithMaskBit(): void
    {
        $frame = Frame::text(str_repeat('x', 200), mask: true);
        $frameHead = new MediumFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        // Second byte: mask bit (128) + marker (126) = 254
        $this->assertSame(254, $bytes[2]);
    }
}
