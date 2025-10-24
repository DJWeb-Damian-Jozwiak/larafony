<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Attributes;

use Larafony\Framework\Validation\Attributes\Min;
use Larafony\Framework\Validation\Attributes\Max;
use PHPUnit\Framework\TestCase;

class MinMaxTest extends TestCase
{
    public function testMinValidatesValueAboveMinimum(): void
    {
        $rule = new Min(5);
        $rule->withData([]);

        $this->assertTrue($rule->validate(5));
        $this->assertTrue($rule->validate(10));
        $this->assertTrue($rule->validate(100));
    }

    public function testMinFailsOnValueBelowMinimum(): void
    {
        $rule = new Min(5);
        $rule->withData([]);

        $this->assertFalse($rule->validate(4));
        $this->assertFalse($rule->validate(0));
        $this->assertFalse($rule->validate(-10));
    }

    public function testMaxValidatesValueBelowMaximum(): void
    {
        $rule = new Max(10);
        $rule->withData([]);

        $this->assertTrue($rule->validate(10));
        $this->assertTrue($rule->validate(5));
        $this->assertTrue($rule->validate(0));
    }

    public function testMaxFailsOnValueAboveMaximum(): void
    {
        $rule = new Max(10);
        $rule->withData([]);

        $this->assertFalse($rule->validate(11));
        $this->assertFalse($rule->validate(100));
    }

    public function testMinHasDynamicMessage(): void
    {
        $rule = new Min(5);

        $this->assertStringContainsString('5', $rule->message);
    }

    public function testMaxHasDynamicMessage(): void
    {
        $rule = new Max(10);

        $this->assertStringContainsString('10', $rule->message);
    }
}
