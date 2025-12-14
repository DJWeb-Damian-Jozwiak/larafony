<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol\Asserts;

use Larafony\Framework\WebSockets\Protocol\Asserts\AssertMinimumFrameLength;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AssertMinimumFrameLengthTest extends TestCase
{
    public function testPassesWithMinimumTwoBytes(): void
    {
        AssertMinimumFrameLength::assert("\x81\x05");

        $this->assertTrue(true);
    }

    public function testPassesWithMoreThanTwoBytes(): void
    {
        AssertMinimumFrameLength::assert("\x81\x05Hello");

        $this->assertTrue(true);
    }

    public function testThrowsWithOneByte(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient data for WebSocket frame: expected at least 2 bytes, got 1');

        AssertMinimumFrameLength::assert("\x81");
    }

    public function testThrowsWithEmptyString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient data for WebSocket frame: expected at least 2 bytes, got 0');

        AssertMinimumFrameLength::assert('');
    }
}
