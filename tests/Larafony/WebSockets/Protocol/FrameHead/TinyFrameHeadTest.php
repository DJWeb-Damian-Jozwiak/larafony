<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol\FrameHead;

use Larafony\Framework\WebSockets\Protocol\Frame;
use Larafony\Framework\WebSockets\Protocol\FrameHead\TinyFrameHead;
use PHPUnit\Framework\TestCase;

final class TinyFrameHeadTest extends TestCase
{
    public function testEncodesTwoByteHeaderForTextFrame(): void
    {
        $frame = Frame::text('Hello');
        $frameHead = new TinyFrameHead($frame);

        $encoded = $frameHead->encode();

        $this->assertSame(2, strlen($encoded));
        $bytes = unpack('C*', $encoded);

        // First byte: FIN(1) + opcode(1 for text) = 0x81
        $this->assertSame(0x81, $bytes[1]);
        // Second byte: no mask + length 5
        $this->assertSame(5, $bytes[2]);
    }

    public function testEncodesWithMaskBit(): void
    {
        $frame = Frame::text('Hi', mask: true);
        $frameHead = new TinyFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        // Second byte: mask bit (128) + length 2 = 130
        $this->assertSame(130, $bytes[2]);
    }

    public function testEncodesPingFrame(): void
    {
        $frame = Frame::ping();
        $frameHead = new TinyFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        // First byte: FIN(1) + opcode(9 for ping) = 0x89
        $this->assertSame(0x89, $bytes[1]);
    }

    public function testEncodesMaxTinyLength(): void
    {
        $frame = Frame::text(str_repeat('x', 125));
        $frameHead = new TinyFrameHead($frame);

        $encoded = $frameHead->encode();
        $bytes = unpack('C*', $encoded);

        $this->assertSame(125, $bytes[2]);
    }
}
