<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Attributes;

use Larafony\Framework\Validation\Attributes\MinLength;
use Larafony\Framework\Validation\Attributes\MaxLength;
use Larafony\Framework\Validation\Attributes\Length;
use PHPUnit\Framework\TestCase;

class LengthTest extends TestCase
{
    public function testMinLengthValidatesStringAboveMinimum(): void
    {
        $rule = new MinLength(3);
        $rule->withData([]);

        $this->assertTrue($rule->validate('abc'));
        $this->assertTrue($rule->validate('abcd'));
        $this->assertTrue($rule->validate('long string'));
    }

    public function testMinLengthFailsOnStringBelowMinimum(): void
    {
        $rule = new MinLength(3);
        $rule->withData([]);

        $this->assertFalse($rule->validate('ab'));
        $this->assertFalse($rule->validate('a'));
        $this->assertFalse($rule->validate(''));
    }

    public function testMinLengthHandlesNullAsEmptyString(): void
    {
        $rule = new MinLength(1);
        $rule->withData([]);

        $this->assertFalse($rule->validate(null));
    }

    public function testMaxLengthValidatesStringBelowMaximum(): void
    {
        $rule = new MaxLength(5);
        $rule->withData([]);

        $this->assertTrue($rule->validate('abc'));
        $this->assertTrue($rule->validate('abcde'));
        $this->assertTrue($rule->validate(''));
    }

    public function testMaxLengthFailsOnStringAboveMaximum(): void
    {
        $rule = new MaxLength(5);
        $rule->withData([]);

        $this->assertFalse($rule->validate('abcdef'));
        $this->assertFalse($rule->validate('too long string'));
    }

    public function testLengthValidatesStringInRange(): void
    {
        $rule = new Length(3, 5);
        $rule->withData([]);

        $this->assertTrue($rule->validate('abc'));
        $this->assertTrue($rule->validate('abcd'));
        $this->assertTrue($rule->validate('abcde'));
    }

    public function testLengthFailsOutsideRange(): void
    {
        $rule = new Length(3, 5);
        $rule->withData([]);

        $this->assertFalse($rule->validate('ab'));
        $this->assertFalse($rule->validate('abcdef'));
    }

    public function testLengthHasDynamicMessage(): void
    {
        $rule = new Length(3, 10);

        $this->assertStringContainsString('3', $rule->message);
        $this->assertStringContainsString('10', $rule->message);
    }
}
