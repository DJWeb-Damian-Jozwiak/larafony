<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Attributes;

use Larafony\Framework\Validation\Attributes\Confirmed;
use PHPUnit\Framework\TestCase;

class ConfirmedTest extends TestCase
{
    public function testValidatesMatchingConfirmationField(): void
    {
        $rule = new Confirmed();
        $rule->withData(['password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->withFieldName('password');

        $this->assertTrue($rule->validate('secret123'));
    }

    public function testFailsOnMismatchedConfirmation(): void
    {
        $rule = new Confirmed();
        $rule->withData(['password' => 'secret123', 'password_confirmation' => 'different'])
            ->withFieldName('password');

        $this->assertFalse($rule->validate('secret123'));
    }

    public function testFailsWhenConfirmationFieldMissing(): void
    {
        $rule = new Confirmed();
        $rule->withData(['password' => 'secret123'])
            ->withFieldName('password');

        $this->assertFalse($rule->validate('secret123'));
    }

    public function testUsesCustomConfirmationFieldName(): void
    {
        $rule = new Confirmed('password_repeat');
        $rule->withData(['password' => 'secret123', 'password_repeat' => 'secret123'])
            ->withFieldName('password');

        $this->assertTrue($rule->validate('secret123'));
    }

    public function testCustomConfirmationFieldMismatch(): void
    {
        $rule = new Confirmed('password_repeat');
        $rule->withData(['password' => 'secret123', 'password_repeat' => 'different'])
            ->withFieldName('password');

        $this->assertFalse($rule->validate('secret123'));
    }

    public function testEmailConfirmation(): void
    {
        $rule = new Confirmed();
        $rule->withData([
            'email' => 'test@example.com',
            'email_confirmation' => 'test@example.com'
        ])->withFieldName('email');

        $this->assertTrue($rule->validate('test@example.com'));
    }

    public function testHasDefaultMessage(): void
    {
        $rule = new Confirmed();

        $this->assertSame('Confirmation does not match', $rule->message);
    }

    public function testCanUseCustomMessage(): void
    {
        $rule = new Confirmed(message: 'Passwords must match');

        $this->assertSame('Passwords must match', $rule->message);
    }
}
