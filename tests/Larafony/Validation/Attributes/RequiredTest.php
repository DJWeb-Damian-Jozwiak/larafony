<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Attributes;

use Larafony\Framework\Validation\Attributes\Required;
use PHPUnit\Framework\TestCase;

class RequiredTest extends TestCase
{
    public function testValidatesNonNullValue(): void
    {
        $rule = new Required();
        $rule->withData([]);

        $this->assertTrue($rule->validate('value'));
        $this->assertTrue($rule->validate(''));
        $this->assertTrue($rule->validate(0));
        $this->assertTrue($rule->validate(false));
    }

    public function testFailsOnNullValue(): void
    {
        $rule = new Required();
        $rule->withData([]);

        $this->assertFalse($rule->validate(null));
    }

    public function testHasDefaultMessage(): void
    {
        $rule = new Required();

        $this->assertSame('This field is required', $rule->message);
    }

    public function testCanUseCustomMessage(): void
    {
        $rule = new Required('Custom required message');

        $this->assertSame('Custom required message', $rule->message);
    }
}
