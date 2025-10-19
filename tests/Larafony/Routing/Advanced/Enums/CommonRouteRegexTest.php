<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\Enums;

use Larafony\Framework\Routing\Advanced\Enums\CommonRouteRegex;
use Larafony\Framework\Tests\TestCase;

class CommonRouteRegexTest extends TestCase
{
    public function testDigitsPattern(): void
    {
        $pattern = CommonRouteRegex::DIGITS->value;

        $this->assertSame(1, preg_match("/$pattern/", '123'));
        $this->assertSame(0, preg_match("/$pattern/", 'abc'));
    }

    public function testUuidPattern(): void
    {
        $pattern = CommonRouteRegex::UUID->value;

        $this->assertSame(1, preg_match("/$pattern/", '550e8400-e29b-41d4-a716-446655440000'));
        $this->assertSame(0, preg_match("/$pattern/", 'not-a-uuid'));
    }

    public function testSlugPattern(): void
    {
        $pattern = CommonRouteRegex::SLUG->value;

        $this->assertSame(1, preg_match("/^$pattern$/", 'hello-world'));
        $this->assertSame(1, preg_match("/^$pattern$/", 'my-blog-post-123'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'Hello World'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'hello_world'));
    }

    public function testAlphaPattern(): void
    {
        $pattern = CommonRouteRegex::ALPHA->value;

        $this->assertSame(1, preg_match("/$pattern/", 'Hello'));
        $this->assertSame(1, preg_match("/$pattern/", 'ABC'));
        $this->assertSame(0, preg_match("/$pattern/", '123'));
    }

    public function testAlphaLowerPattern(): void
    {
        $pattern = CommonRouteRegex::ALPHA_LOWER->value;

        $this->assertSame(1, preg_match("/^$pattern$/", 'hello'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'Hello'));
        $this->assertSame(0, preg_match("/^$pattern$/", '123'));
    }

    public function testAlphaUpperPattern(): void
    {
        $pattern = CommonRouteRegex::ALPHA_UPPER->value;

        $this->assertSame(1, preg_match("/^$pattern$/", 'HELLO'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'Hello'));
        $this->assertSame(0, preg_match("/^$pattern$/", '123'));
    }

    public function testAlphaDashPattern(): void
    {
        $pattern = CommonRouteRegex::ALPHA_DASH->value;

        $this->assertSame(1, preg_match("/^$pattern$/", 'hello-world'));
        $this->assertSame(1, preg_match("/^$pattern$/", 'Hello-World'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'hello_world'));
        $this->assertSame(0, preg_match("/^$pattern$/", '123'));
    }

    public function testAlphaNumPattern(): void
    {
        $pattern = CommonRouteRegex::ALPHA_NUM->value;

        $this->assertSame(1, preg_match("/^$pattern$/", 'hello123'));
        $this->assertSame(1, preg_match("/^$pattern$/", 'ABC123'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'hello-world'));
    }

    public function testIsoDatePattern(): void
    {
        $pattern = CommonRouteRegex::ISO_DATE->value;

        $this->assertSame(1, preg_match("/$pattern/", '2025-10-19'));
        $this->assertSame(1, preg_match("/$pattern/", '2000-01-01'));
        $this->assertSame(0, preg_match("/$pattern/", '19-10-2025'));
        $this->assertSame(0, preg_match("/$pattern/", '2025/10/19'));
    }

    public function testIsoDatetimePattern(): void
    {
        $pattern = CommonRouteRegex::ISO_DATETIME->value;

        $this->assertSame(1, preg_match("/$pattern/", '2025-10-19T14:30:00'));
        $this->assertSame(0, preg_match("/$pattern/", '2025-10-19 14:30:00'));
    }

    public function testEmailPattern(): void
    {
        $pattern = CommonRouteRegex::EMAIL->value;

        $this->assertSame(1, preg_match("/$pattern/", 'test@example.com'));
        $this->assertSame(1, preg_match("/$pattern/", 'user+tag@domain.co.uk'));
        $this->assertSame(0, preg_match("/$pattern/", 'invalid-email'));
    }

    public function testUsernamePattern(): void
    {
        $pattern = CommonRouteRegex::USERNAME->value;

        $this->assertSame(1, preg_match("/^$pattern$/", 'john_doe'));
        $this->assertSame(1, preg_match("/^$pattern$/", 'user-123'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'ab')); // Too short
        $this->assertSame(0, preg_match("/^$pattern$/", 'user@name')); // Invalid char
    }

    public function testHexColorPattern(): void
    {
        $pattern = CommonRouteRegex::HEX_COLOR->value;

        $this->assertSame(1, preg_match("/$pattern/", 'FF5733'));
        $this->assertSame(1, preg_match("/$pattern/", '000000'));
        $this->assertSame(0, preg_match("/$pattern/", 'FFF')); // Too short
        $this->assertSame(0, preg_match("/$pattern/", 'GGGGGG')); // Invalid hex
    }

    public function testIpV4Pattern(): void
    {
        $pattern = CommonRouteRegex::IP_V4->value;

        $this->assertSame(1, preg_match("/^$pattern$/", '192.168.1.1'));
        $this->assertSame(1, preg_match("/^$pattern$/", '10.0.0.1'));
        $this->assertSame(1, preg_match("/^$pattern$/", '999.999.999.999')); // Matches pattern (validation is separate concern)
    }

    public function testCountryCodePattern(): void
    {
        $pattern = CommonRouteRegex::COUNTRY_CODE->value;

        $this->assertSame(1, preg_match("/^$pattern$/", 'US'));
        $this->assertSame(1, preg_match("/^$pattern$/", 'PL'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'us'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'USA'));
    }

    public function testLocalePattern(): void
    {
        $pattern = CommonRouteRegex::LOCALE->value;

        $this->assertSame(1, preg_match("/^$pattern$/", 'en'));
        $this->assertSame(1, preg_match("/^$pattern$/", 'en_US'));
        $this->assertSame(1, preg_match("/^$pattern$/", 'pl_PL'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'EN'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'en-US'));
    }

    public function testYearPattern(): void
    {
        $pattern = CommonRouteRegex::YEAR->value;

        $this->assertSame(1, preg_match("/$pattern/", '2025'));
        $this->assertSame(1, preg_match("/$pattern/", '1999'));
        $this->assertSame(0, preg_match("/$pattern/", '25'));
    }

    public function testMonthPattern(): void
    {
        $pattern = CommonRouteRegex::MONTH->value;

        $this->assertSame(1, preg_match("/$pattern/", '01'));
        $this->assertSame(1, preg_match("/$pattern/", '12'));
        $this->assertSame(0, preg_match("/$pattern/", '13'));
        $this->assertSame(0, preg_match("/$pattern/", '00'));
    }

    public function testDayPattern(): void
    {
        $pattern = CommonRouteRegex::DAY->value;

        $this->assertSame(1, preg_match("/$pattern/", '01'));
        $this->assertSame(1, preg_match("/$pattern/", '31'));
        $this->assertSame(0, preg_match("/$pattern/", '32'));
        $this->assertSame(0, preg_match("/$pattern/", '00'));
    }

    public function testCurrencyPattern(): void
    {
        $pattern = CommonRouteRegex::CURRENCY->value;

        $this->assertSame(1, preg_match("/$pattern/", 'USD'));
        $this->assertSame(1, preg_match("/$pattern/", 'EUR'));
        $this->assertSame(0, preg_match("/$pattern/", 'usd'));
        $this->assertSame(0, preg_match("/$pattern/", 'US'));
    }

    public function testPhonePattern(): void
    {
        $pattern = CommonRouteRegex::PHONE->value;

        $this->assertSame(1, preg_match("/^$pattern$/", '+48123456789'));
        $this->assertSame(1, preg_match("/^$pattern$/", '123456789'));
        $this->assertSame(0, preg_match("/^$pattern$/", '+0123456789')); // Leading 0 after +
    }

    public function testSemverPattern(): void
    {
        $pattern = CommonRouteRegex::SEMVER->value;

        $this->assertSame(1, preg_match("/^$pattern$/", '1.0.0'));
        $this->assertSame(1, preg_match("/^$pattern$/", '12.34.56'));
        $this->assertSame(0, preg_match("/^$pattern$/", '1.0'));
        $this->assertSame(0, preg_match("/^$pattern$/", 'v1.0.0'));
    }

    public function testMd5Pattern(): void
    {
        $pattern = CommonRouteRegex::MD5->value;

        $this->assertSame(1, preg_match("/$pattern/", '5d41402abc4b2a76b9719d911017c592'));
        $this->assertSame(0, preg_match("/$pattern/", '5d41402abc4b2a76b9719d911017c59')); // Too short
        $this->assertSame(0, preg_match("/$pattern/", '5D41402ABC4B2A76B9719D911017C592')); // Uppercase
    }

    public function testSha1Pattern(): void
    {
        $pattern = CommonRouteRegex::SHA1->value;

        $this->assertSame(1, preg_match("/$pattern/", '356a192b7913b04c54574d18c28d46e6395428ab'));
        $this->assertSame(0, preg_match("/$pattern/", '356a192b7913b04c54574d18c28d46e6395428a')); // Too short
    }

    public function testSha256Pattern(): void
    {
        $pattern = CommonRouteRegex::SHA256->value;

        $this->assertSame(1, preg_match("/$pattern/", 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'));
        $this->assertSame(0, preg_match("/$pattern/", 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b85')); // Too short
    }

    public function testAllPatternsAreValidRegex(): void
    {
        foreach (CommonRouteRegex::cases() as $case) {
            $pattern = "/{$case->value}/";
            $result = @preg_match($pattern, '');

            $this->assertNotFalse($result, "Pattern {$case->name} is not a valid regex");
        }
    }
}
