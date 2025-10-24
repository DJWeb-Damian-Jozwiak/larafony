<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Attributes;

use Larafony\Framework\Validation\Attributes\RequiredWhen;
use PHPUnit\Framework\TestCase;

class RequiredWhenTest extends TestCase
{
    public function testValidatesWhenConditionIsFalse(): void
    {
        $rule = new RequiredWhen(fn(array $data) => false);
        $rule->withData(['type' => 'personal']);

        $this->assertTrue($rule->validate(null));
        $this->assertTrue($rule->validate(''));
        $this->assertTrue($rule->validate('value'));
    }

    public function testRequiresValueWhenConditionIsTrue(): void
    {
        $rule = new RequiredWhen(fn(array $data) => $data['type'] === 'business');
        $rule->withData(['type' => 'business']);

        $this->assertTrue($rule->validate('value'));
        $this->assertFalse($rule->validate(null));
    }

    public function testWorksWithComplexConditions(): void
    {
        $rule = new RequiredWhen(
            fn(array $data) => $data['account_type'] === 'business' && $data['country'] === 'PL'
        );

        // Not required - wrong account type
        $rule->withData(['account_type' => 'personal', 'country' => 'PL']);
        $this->assertTrue($rule->validate(null));

        // Not required - wrong country
        $rule->withData(['account_type' => 'business', 'country' => 'US']);
        $this->assertTrue($rule->validate(null));

        // Required - both conditions met
        $rule->withData(['account_type' => 'business', 'country' => 'PL']);
        $this->assertFalse($rule->validate(null));
        $this->assertTrue($rule->validate('value'));
    }

    public function testWorksWithFirstClassCallable(): void
    {
        $isBusinessAccount = fn(array $data): bool => $data['type'] === 'business';

        $rule = new RequiredWhen($isBusinessAccount);
        $rule->withData(['type' => 'business']);

        $this->assertFalse($rule->validate(null));
        $this->assertTrue($rule->validate('company name'));
    }

    public function testHasDefaultMessage(): void
    {
        $rule = new RequiredWhen(fn() => true);

        $this->assertSame('This field is required', $rule->message);
    }

    public function testCanUseCustomMessage(): void
    {
        $rule = new RequiredWhen(fn() => true, 'Custom required message');

        $this->assertSame('Custom required message', $rule->message);
    }
}
