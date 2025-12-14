<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol;

use Larafony\Framework\WebSockets\Protocol\Opcode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Opcode::class)]
final class OpcodeTest extends TestCase
{
    public function testHasCorrectValues(): void
    {
        $this->assertSame(0, Opcode::CONTINUATION->value);
        $this->assertSame(1, Opcode::TEXT->value);
        $this->assertSame(2, Opcode::BINARY->value);
        $this->assertSame(8, Opcode::CLOSE->value);
        $this->assertSame(9, Opcode::PING->value);
        $this->assertSame(10, Opcode::PONG->value);
    }

    public function testIdentifiesControlFrames(): void
    {
        $this->assertFalse(Opcode::CONTINUATION->isControl());
        $this->assertFalse(Opcode::TEXT->isControl());
        $this->assertFalse(Opcode::BINARY->isControl());
        $this->assertTrue(Opcode::CLOSE->isControl());
        $this->assertTrue(Opcode::PING->isControl());
        $this->assertTrue(Opcode::PONG->isControl());
    }

    public function testCanBeCreatedFromValue(): void
    {
        $this->assertSame(Opcode::TEXT, Opcode::from(1));
        $this->assertSame(Opcode::BINARY, Opcode::from(2));
        $this->assertSame(Opcode::CLOSE, Opcode::from(8));
    }
}
