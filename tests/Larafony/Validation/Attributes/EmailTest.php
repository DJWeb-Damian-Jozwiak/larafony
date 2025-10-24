<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Attributes;

use Larafony\Framework\Validation\Attributes\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testValidatesValidEmail(): void
    {
        $rule = new Email();
        $rule->withData([]);

        $this->assertTrue($rule->validate('test@example.com'));
        $this->assertTrue($rule->validate('user+tag@domain.co.uk'));
        $this->assertTrue($rule->validate('first.last@example.com'));
    }

    public function testFailsOnInvalidEmail(): void
    {
        $rule = new Email();
        $rule->withData([]);

        $this->assertFalse($rule->validate('invalid'));
        $this->assertFalse($rule->validate('missing@'));
        $this->assertFalse($rule->validate('@domain.com'));
        $this->assertFalse($rule->validate('missing@domain'));
        $this->assertFalse($rule->validate('spaces in@email.com'));
    }

    public function testHasDefaultMessage(): void
    {
        $rule = new Email();

        $this->assertSame('Invalid email format', $rule->message);
    }

    public function testCanUseCustomMessage(): void
    {
        $rule = new Email('Please provide valid email');

        $this->assertSame('Please provide valid email', $rule->message);
    }
}
