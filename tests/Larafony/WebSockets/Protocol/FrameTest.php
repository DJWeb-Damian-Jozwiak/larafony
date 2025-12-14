<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol;

use Larafony\Framework\WebSockets\Protocol\Frame;
use Larafony\Framework\WebSockets\Protocol\Opcode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Frame::class)]
final class FrameTest extends TestCase
{
    public function testCreatesTextFrame(): void
    {
        $frame = Frame::text('Hello');

        $this->assertTrue($frame->fin);
        $this->assertSame(Opcode::TEXT, $frame->opcode);
        $this->assertFalse($frame->mask);
        $this->assertSame(5, $frame->payloadLength);
        $this->assertNull($frame->maskingKey);
        $this->assertSame('Hello', $frame->payload);
    }

    public function testCreatesMaskedTextFrame(): void
    {
        $frame = Frame::text('Hello', mask: true);

        $this->assertTrue($frame->fin);
        $this->assertSame(Opcode::TEXT, $frame->opcode);
        $this->assertTrue($frame->mask);
        $this->assertSame(5, $frame->payloadLength);
        $this->assertNotNull($frame->maskingKey);
        $this->assertSame(4, strlen($frame->maskingKey));
        $this->assertSame('Hello', $frame->payload);
    }

    public function testCreatesBinaryFrame(): void
    {
        $data = "\x00\x01\x02\x03";
        $frame = Frame::binary($data);

        $this->assertTrue($frame->fin);
        $this->assertSame(Opcode::BINARY, $frame->opcode);
        $this->assertFalse($frame->mask);
        $this->assertSame(4, $frame->payloadLength);
        $this->assertSame($data, $frame->payload);
    }

    public function testCreatesPingFrame(): void
    {
        $frame = Frame::ping('ping-data');

        $this->assertTrue($frame->fin);
        $this->assertSame(Opcode::PING, $frame->opcode);
        $this->assertFalse($frame->mask);
        $this->assertSame('ping-data', $frame->payload);
    }

    public function testCreatesPongFrame(): void
    {
        $frame = Frame::pong('pong-data');

        $this->assertTrue($frame->fin);
        $this->assertSame(Opcode::PONG, $frame->opcode);
        $this->assertFalse($frame->mask);
        $this->assertSame('pong-data', $frame->payload);
    }

    public function testCreatesCloseFrame(): void
    {
        $frame = Frame::close(1000, 'Normal closure');

        $this->assertTrue($frame->fin);
        $this->assertSame(Opcode::CLOSE, $frame->opcode);
        $this->assertFalse($frame->mask);
        $this->assertStringContainsString('Normal closure', $frame->payload);
    }

    public function testCreatesCloseFrameWithDefaultValues(): void
    {
        $frame = Frame::close();

        $this->assertSame(Opcode::CLOSE, $frame->opcode);
        $this->assertSame(2, $frame->payloadLength);
    }
}
