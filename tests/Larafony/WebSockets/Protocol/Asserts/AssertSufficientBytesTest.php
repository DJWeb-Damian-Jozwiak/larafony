<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol\Asserts;

use Larafony\Framework\WebSockets\Protocol\Asserts\AssertSufficientBytes;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AssertSufficientBytesTest extends TestCase
{
    public function testGenericAssertPassesWithExactCount(): void
    {
        AssertSufficientBytes::assert([1, 2, 3], 3, 'test context');

        $this->assertTrue(true);
    }

    public function testGenericAssertPassesWithMoreThanRequired(): void
    {
        AssertSufficientBytes::assert([1, 2, 3, 4, 5], 3, 'test context');

        $this->assertTrue(true);
    }

    public function testGenericAssertThrowsWithInsufficientBytes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient data for test context: expected at least 3 bytes, got 2');

        AssertSufficientBytes::assert([1, 2], 3, 'test context');
    }

    public function testForExtendedLength16PassesWithTwoBytes(): void
    {
        AssertSufficientBytes::forExtendedLength16([0, 126]);

        $this->assertTrue(true);
    }

    public function testForExtendedLength16ThrowsWithOneByte(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient data for extended payload length (16-bit)');

        AssertSufficientBytes::forExtendedLength16([0]);
    }

    public function testForExtendedLength64PassesWithEightBytes(): void
    {
        AssertSufficientBytes::forExtendedLength64([0, 0, 0, 0, 0, 0, 0, 127]);

        $this->assertTrue(true);
    }

    public function testForExtendedLength64ThrowsWithSevenBytes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient data for extended payload length (64-bit)');

        AssertSufficientBytes::forExtendedLength64([0, 0, 0, 0, 0, 0, 0]);
    }

    public function testForMaskingKeyPassesWithFourBytes(): void
    {
        AssertSufficientBytes::forMaskingKey([0x37, 0xfa, 0x21, 0x3d]);

        $this->assertTrue(true);
    }

    public function testForMaskingKeyThrowsWithThreeBytes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient data for masking key');

        AssertSufficientBytes::forMaskingKey([0x37, 0xfa, 0x21]);
    }

    public function testConstantsHaveCorrectValues(): void
    {
        $this->assertSame(2, AssertSufficientBytes::EXTENDED_LENGTH_16BIT);
        $this->assertSame(8, AssertSufficientBytes::EXTENDED_LENGTH_64BIT);
        $this->assertSame(4, AssertSufficientBytes::MASKING_KEY);
    }
}
