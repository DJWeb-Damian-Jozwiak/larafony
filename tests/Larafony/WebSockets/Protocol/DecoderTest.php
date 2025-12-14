<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol;

use Larafony\Framework\WebSockets\Protocol\Decoder;
use Larafony\Framework\WebSockets\Protocol\Encoder;
use Larafony\Framework\WebSockets\Protocol\Frame;
use Larafony\Framework\WebSockets\Protocol\Opcode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Decoder::class)]
final class DecoderTest extends TestCase
{
    public function testDecodesSimpleTextFrame(): void
    {
        $data = "\x81\x05Hello";

        $frame = Decoder::decode($data);

        $this->assertTrue($frame->fin);
        $this->assertSame(Opcode::TEXT, $frame->opcode);
        $this->assertFalse($frame->mask);
        $this->assertSame(5, $frame->payloadLength);
        $this->assertSame('Hello', $frame->payload);
    }

    public function testDecodesMaskedFrame(): void
    {
        $maskingKey = "\x37\xfa\x21\x3d";
        $payload = 'Hello';
        $maskedPayload = Encoder::applyMask($payload, $maskingKey);

        $data = "\x81\x85" . $maskingKey . $maskedPayload;

        $frame = Decoder::decode($data);

        $this->assertTrue($frame->fin);
        $this->assertSame(Opcode::TEXT, $frame->opcode);
        $this->assertTrue($frame->mask);
        $this->assertSame('Hello', $frame->payload);
    }

    public function testDecodesFrameWith126ByteLength(): void
    {
        $payload = str_repeat('A', 200);
        $data = "\x81\x7e\x00\xc8" . $payload;

        $frame = Decoder::decode($data);

        $this->assertSame(200, $frame->payloadLength);
        $this->assertSame($payload, $frame->payload);
    }

    public function testDecodesPingFrame(): void
    {
        $data = "\x89\x04ping";

        $frame = Decoder::decode($data);

        $this->assertSame(Opcode::PING, $frame->opcode);
        $this->assertSame('ping', $frame->payload);
    }

    public function testDecodesPongFrame(): void
    {
        $data = "\x8a\x04pong";

        $frame = Decoder::decode($data);

        $this->assertSame(Opcode::PONG, $frame->opcode);
        $this->assertSame('pong', $frame->payload);
    }

    public function testDecodesCloseFrame(): void
    {
        $data = "\x88\x02\x03\xe8";

        $frame = Decoder::decode($data);

        $this->assertSame(Opcode::CLOSE, $frame->opcode);
    }

    public function testThrowsOnInsufficientData(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient data for WebSocket frame');

        Decoder::decode("\x81");
    }

    public function testDecodesBinaryFrame(): void
    {
        $payload = "\x00\x01\x02\x03";
        $data = "\x82\x04" . $payload;

        $frame = Decoder::decode($data);

        $this->assertSame(Opcode::BINARY, $frame->opcode);
        $this->assertSame($payload, $frame->payload);
    }

    public function testRoundtripsEncodeDecode(): void
    {
        $original = Frame::text('Test message');
        $encoded = Encoder::encode($original);
        $decoded = Decoder::decode($encoded);

        $this->assertSame($original->fin, $decoded->fin);
        $this->assertSame($original->opcode, $decoded->opcode);
        $this->assertSame($original->payload, $decoded->payload);
    }
}
