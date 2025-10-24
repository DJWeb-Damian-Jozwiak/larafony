<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Attributes;

use Larafony\Framework\Validation\Attributes\RequiredUnless;
use PHPUnit\Framework\TestCase;

class RequiredUnlessTest extends TestCase
{
    public function testValidatesWhenConditionIsTrue(): void
    {
        $rule = new RequiredUnless(fn(array $data) => true);
        $rule->withData(['has_phone' => true]);

        $this->assertTrue($rule->validate(null));
        $this->assertTrue($rule->validate(''));
        $this->assertTrue($rule->validate('value'));
    }

    public function testRequiresValueWhenConditionIsFalse(): void
    {
        $rule = new RequiredUnless(fn(array $data) => isset($data['phone']));
        $rule->withData(['email' => 'test@example.com']);

        $this->assertTrue($rule->validate('test@example.com'));
        $this->assertFalse($rule->validate(null));
    }

    public function testEmailRequiredUnlessHasPhone(): void
    {
        $rule = new RequiredUnless(fn(array $data) => !empty($data['phone']));

        // Has phone - email not required
        $rule->withData(['phone' => '123456789']);
        $this->assertTrue($rule->validate(null));

        // No phone - email required
        $rule->withData([]);
        $this->assertFalse($rule->validate(null));
        $this->assertTrue($rule->validate('email@example.com'));
    }

    public function testPasswordRequiredUnlessOauth(): void
    {
        $rule = new RequiredUnless(fn(array $data) => $data['auth_method'] === 'oauth');

        // OAuth - password not required
        $rule->withData(['auth_method' => 'oauth']);
        $this->assertTrue($rule->validate(null));

        // Standard auth - password required
        $rule->withData(['auth_method' => 'standard']);
        $this->assertFalse($rule->validate(null));
        $this->assertTrue($rule->validate('password123'));
    }

    public function testHasDefaultMessage(): void
    {
        $rule = new RequiredUnless(fn() => false);

        $this->assertSame('This field is required', $rule->message);
    }

    public function testCanUseCustomMessage(): void
    {
        $rule = new RequiredUnless(fn() => false, 'This field is needed');

        $this->assertSame('This field is needed', $rule->message);
    }
}
