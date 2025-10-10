<?php

declare(strict_types=1);

namespace Larafony\Tests\Config\Environment\Parser;

use PHPUnit\Framework\TestCase;
use Larafony\Framework\Config\Environment\Parser\ValueParser;

class ValueParserTest extends TestCase
{
    private ValueParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ValueParser();
    }

    public function testUnquotedValue(): void
    {
        $result = $this->parser->parse('simple_value');

        $this->assertEquals('simple_value', $result['value']);
        $this->assertFalse($result['is_quoted']);
    }

    public function testDoubleQuotedValue(): void
    {
        $result = $this->parser->parse('"quoted value"');

        $this->assertEquals('quoted value', $result['value']);
        $this->assertTrue($result['is_quoted']);
    }

    public function testSingleQuotedValue(): void
    {
        $result = $this->parser->parse("'single quoted'");

        $this->assertEquals('single quoted', $result['value']);
        $this->assertTrue($result['is_quoted']);
    }

    public function testEscapeSequencesInDoubleQuotes(): void
    {
        $result = $this->parser->parse('"Line 1\nLine 2\tTabbed"');

        $expected = "Line 1\nLine 2\tTabbed";
        $this->assertEquals($expected, $result['value']);
    }

    public function testNotProcessEscapeSequencesInSingleQuotes(): void
    {
        $result = $this->parser->parse("'Line 1\\nLine 2'");

        // W single quotes, \n pozostaje jako literalny \n
        $this->assertEquals('Line 1\\nLine 2', $result['value']);
    }

    public function testEscapedQuotesInDoubleQuotes(): void
    {
        $result = $this->parser->parse('"He said \"hello\""');

        $this->assertEquals('He said "hello"', $result['value']);
    }

    public function testEscapedBackslash(): void
    {
        $result = $this->parser->parse('"C:\\\\path\\\\to\\\\file"');

        $this->assertEquals('C:\path\to\file', $result['value']);
    }

    public function testWhitespaceFromUnquotedValues(): void
    {
        $result = $this->parser->parse('  spaced  ');

        $this->assertEquals('spaced', $result['value']);
        $this->assertFalse($result['is_quoted']);
    }

    public function testEmptyValue(): void
    {
        $result = $this->parser->parse('');

        $this->assertEquals('', $result['value']);
        $this->assertFalse($result['is_quoted']);
    }

    public function testEmptyQuotedValue(): void
    {
        $result = $this->parser->parse('""');

        $this->assertEquals('', $result['value']);
        $this->assertTrue($result['is_quoted']);
    }
}
