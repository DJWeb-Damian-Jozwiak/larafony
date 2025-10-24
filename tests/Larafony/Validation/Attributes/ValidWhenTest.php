<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Attributes;

use Larafony\Framework\Validation\Attributes\ValidWhen;
use PHPUnit\Framework\TestCase;

class ValidWhenTest extends TestCase
{
    public function testValidatesWithSimpleClosure(): void
    {
        $rule = new ValidWhen(fn(mixed $value, array $data) => $value === 'valid');
        $rule->withData([]);

        $this->assertTrue($rule->validate('valid'));
        $this->assertFalse($rule->validate('invalid'));
    }

    public function testPasswordConfirmation(): void
    {
        $rule = new ValidWhen(
            fn(mixed $value, array $data) => $value === $data['password']
        );
        $rule->withData(['password' => 'secret123']);

        $this->assertTrue($rule->validate('secret123'));
        $this->assertFalse($rule->validate('wrong'));
        $this->assertFalse($rule->validate(''));
    }

    public function testAgeValidationWithContext(): void
    {
        $rule = new ValidWhen(
            fn(mixed $value, array $data) => $value >= ($data['country'] === 'US' ? 21 : 18)
        );

        // US - requires 21
        $rule->withData(['country' => 'US']);
        $this->assertTrue($rule->validate(21));
        $this->assertTrue($rule->validate(25));
        $this->assertFalse($rule->validate(18));
        $this->assertFalse($rule->validate(20));

        // Other country - requires 18
        $rule->withData(['country' => 'UK']);
        $this->assertTrue($rule->validate(18));
        $this->assertTrue($rule->validate(20));
        $this->assertFalse($rule->validate(17));
    }

    public function testDateRangeValidation(): void
    {
        $rule = new ValidWhen(
            fn($end, $data) => strtotime($end) > strtotime($data['start_date'])
        );
        $rule->withData(['start_date' => '2024-01-01']);

        $this->assertTrue($rule->validate('2024-01-02'));
        $this->assertTrue($rule->validate('2024-12-31'));
        $this->assertFalse($rule->validate('2024-01-01'));
        $this->assertFalse($rule->validate('2023-12-31'));
    }

    public function testStrongPasswordValidation(): void
    {
        $isStrong = fn(mixed $value, array $data): bool =>
            strlen($value) >= 8
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[0-9]/', $value)
            && preg_match('/[^A-Za-z0-9]/', $value);

        $rule = new ValidWhen($isStrong);
        $rule->withData([]);

        $this->assertTrue($rule->validate('Strong123!'));
        $this->assertTrue($rule->validate('P@ssw0rd'));
        $this->assertFalse($rule->validate('weak'));
        $this->assertFalse($rule->validate('NoNumbers!'));
        $this->assertFalse($rule->validate('noupppercase123!'));
        $this->assertFalse($rule->validate('NoSpecial123'));
    }

    public function testHasCustomMessage(): void
    {
        $rule = new ValidWhen(fn() => true, 'Custom validation message');

        $this->assertSame('Custom validation message', $rule->message);
    }

    public function testHasDefaultMessage(): void
    {
        $rule = new ValidWhen(fn() => true);

        $this->assertSame('Validation failed', $rule->message);
    }

    public function testMultipleValidWhenOnSameField(): void
    {
        $rule1 = new ValidWhen(fn($v) => strlen($v) >= 8, 'Too short');
        $rule2 = new ValidWhen(fn($v) => strlen($v) <= 20, 'Too long');

        $rule1->withData([]);
        $rule2->withData([]);

        $value = 'just right';
        $this->assertTrue($rule1->validate($value));
        $this->assertTrue($rule2->validate($value));

        $this->assertFalse($rule1->validate('short'));
        $this->assertFalse($rule2->validate('this is way too long string'));
    }
}
