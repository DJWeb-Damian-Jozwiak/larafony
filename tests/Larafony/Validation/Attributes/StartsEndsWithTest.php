<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Attributes;

use Larafony\Framework\Validation\Attributes\StartsWith;
use Larafony\Framework\Validation\Attributes\EndsWith;
use PHPUnit\Framework\TestCase;

class StartsEndsWithTest extends TestCase
{
    public function testStartsWithValidatesCorrectPrefix(): void
    {
        $rule = new StartsWith(['http://', 'https://']);
        $rule->withData([]);

        $this->assertTrue($rule->validate('http://example.com'));
        $this->assertTrue($rule->validate('https://example.com'));
    }

    public function testStartsWithFailsOnWrongPrefix(): void
    {
        $rule = new StartsWith(['http://', 'https://']);
        $rule->withData([]);

        $this->assertFalse($rule->validate('ftp://example.com'));
        $this->assertFalse($rule->validate('example.com'));
    }

    public function testStartsWithHandlesNull(): void
    {
        $rule = new StartsWith(['test']);
        $rule->withData([]);

        $this->assertFalse($rule->validate(null));
    }

    public function testEndsWithValidatesCorrectSuffix(): void
    {
        $rule = new EndsWith(['.com', '.org', '.net']);
        $rule->withData([]);

        $this->assertTrue($rule->validate('example.com'));
        $this->assertTrue($rule->validate('test.org'));
        $this->assertTrue($rule->validate('site.net'));
    }

    public function testEndsWithFailsOnWrongSuffix(): void
    {
        $rule = new EndsWith(['.com', '.org']);
        $rule->withData([]);

        $this->assertFalse($rule->validate('example.pl'));
        $this->assertFalse($rule->validate('test.uk'));
    }

    public function testEndsWithHandlesNull(): void
    {
        $rule = new EndsWith(['.com']);
        $rule->withData([]);

        $this->assertFalse($rule->validate(null));
    }

    public function testFiltersNonStringValues(): void
    {
        $rule = new StartsWith(['test', 123, null]);
        $rule->withData([]);

        $this->assertTrue($rule->validate('test123'));
    }
}
