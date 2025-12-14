<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol;

use Larafony\Framework\WebSockets\Protocol\Encoder;
use Larafony\Framework\WebSockets\Protocol\Frame;
use Larafony\Framework\WebSockets\Protocol\Opcode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Encoder::class)]
final class EncoderTest extends TestCase
{
    public function testEncodesSimpleTextFrame(): void
    {
        $frame = Frame::text('Hello');
        $encoded = Encoder::encode($frame);

        $this->assertSame(0x81, ord($encoded[0]));
        $this->assertSame(5, ord($encoded[1]));
        $this->assertSame('Hello', substr($encoded, 2));
    }

    public function testEncodesFrameWith126BytePayload(): void
    {
        $payload = str_repeat('A', 126);
        $frame = Frame::text($payload);
        $encoded = Encoder::encode($frame);

        $this->assertSame(0x81, ord($encoded[0]));
        $this->assertSame(126, ord($encoded[1]));
        $this->assertSame(0, ord($encoded[2]));
        $this->assertSame(126, ord($encoded[3]));
        $this->assertSame($payload, substr($encoded, 4));
    }

    public function testEncodesFrameWithLargePayload(): void
    {
        $payload = str_repeat('B', 65536);
        $frame = Frame::text($payload);
        $encoded = Encoder::encode($frame);

        $this->assertSame(0x81, ord($encoded[0]));
        $this->assertSame(127, ord($encoded[1]));
        $this->assertSame($payload, substr($encoded, 10));
    }

    public function testEncodesMaskedFrame(): void
    {
        $frame = new Frame(
            fin: true,
            opcode: Opcode::TEXT,
            mask: true,
            payloadLength: 5,
            maskingKey: "\x12\x34\x56\x78",
            payload: 'Hello',
        );

        $encoded = Encoder::encode($frame);

        $this->assertSame(0x81, ord($encoded[0]));
        $this->assertSame(133, ord($encoded[1]));
        $this->assertSame("\x12\x34\x56\x78", substr($encoded, 2, 4));
    }

    public function testEncodesPingFrame(): void
    {
        $frame = Frame::ping('test');
        $encoded = Encoder::encode($frame);

        $this->assertSame(0x89, ord($encoded[0]));
        $this->assertSame(4, ord($encoded[1]));
    }

    public function testEncodesCloseFrame(): void
    {
        $frame = Frame::close(1000);
        $encoded = Encoder::encode($frame);

        $this->assertSame(0x88, ord($encoded[0]));
    }

    public function testAppliesMaskCorrectly(): void
    {
        $payload = 'AAAA';
        $maskingKey = "\x00\x00\x00\x00";

        $masked = Encoder::applyMask($payload, $maskingKey);

        $this->assertSame('AAAA', $masked);
    }

    public function testAppliesMaskWithXor(): void
    {
        $payload = "\x00\x00\x00\x00";
        $maskingKey = "\x12\x34\x56\x78";

        $masked = Encoder::applyMask($payload, $maskingKey);

        $this->assertSame("\x12\x34\x56\x78", $masked);
    }
}
