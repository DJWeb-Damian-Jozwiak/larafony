<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Input;

use Larafony\Framework\Console\Input\ValueCaster;
use PHPUnit\Framework\TestCase;

final class ValueCasterTest extends TestCase
{
    public function testCastsIntegerStringsToInt(): void
    {
        $this->assertSame(123, ValueCaster::cast('123'));
        $this->assertFalse(ValueCaster::cast('0')); // '0' maps to false in BOOLEAN_VALUES
        $this->assertSame(999, ValueCaster::cast('999'));
    }

    public function testCastsFloatStringsToFloat(): void
    {
        $this->assertSame(3.14, ValueCaster::cast('3.14'));
        $this->assertSame(0.5, ValueCaster::cast('0.5'));
        $this->assertSame(99.99, ValueCaster::cast('99.99'));
    }

    public function testCastsTrueStringsToBoolean(): void
    {
        $this->assertTrue(ValueCaster::cast('1'));
        $this->assertTrue(ValueCaster::cast('on'));
        $this->assertTrue(ValueCaster::cast('true'));
        $this->assertTrue(ValueCaster::cast('yes'));
    }

    public function testCastsFalseStringsToBoolean(): void
    {
        $this->assertFalse(ValueCaster::cast('0'));
        $this->assertFalse(ValueCaster::cast('off'));
        $this->assertFalse(ValueCaster::cast('false'));
        $this->assertFalse(ValueCaster::cast('no'));
    }

    public function testReturnsStringForNonMatchingValues(): void
    {
        $this->assertSame('hello', ValueCaster::cast('hello'));
        $this->assertSame('world', ValueCaster::cast('world'));
        $this->assertSame('text123', ValueCaster::cast('text123'));
    }

    public function testHandlesEmptyString(): void
    {
        $this->assertSame('', ValueCaster::cast(''));
    }

    public function testHandlesNegativeNumbers(): void
    {
        $this->assertSame(-123.45, ValueCaster::cast('-123.45'));
    }
}
