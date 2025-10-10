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

    public function test_parses_unquoted_value(): void
    {
        $result = $this->parser->parse('simple_value');

        $this->assertEquals('simple_value', $result['value']);
        $this->assertFalse($result['is_quoted']);
    }

    public function test_parses_double_quoted_value(): void
    {
        $result = $this->parser->parse('"quoted value"');

        $this->assertEquals('quoted value', $result['value']);
        $this->assertTrue($result['is_quoted']);
    }

    public function test_parses_single_quoted_value(): void
    {
        $result = $this->parser->parse("'single quoted'");

        $this->assertEquals('single quoted', $result['value']);
        $this->assertTrue($result['is_quoted']);
    }

    public function test_processes_escape_sequences_in_double_quotes(): void
    {
        $result = $this->parser->parse('"Line 1\nLine 2\tTabbed"');

        $expected = "Line 1\nLine 2\tTabbed";
        $this->assertEquals($expected, $result['value']);
    }

    public function test_does_not_process_escape_sequences_in_single_quotes(): void
    {
        $result = $this->parser->parse("'Line 1\\nLine 2'");

        // W single quotes, \n pozostaje jako literalny \n
        $this->assertEquals('Line 1\\nLine 2', $result['value']);
    }

    public function test_handles_escaped_quotes_in_double_quotes(): void
    {
        $result = $this->parser->parse('"He said \"hello\""');

        $this->assertEquals('He said "hello"', $result['value']);
    }

    public function test_handles_escaped_backslash(): void
    {
        $result = $this->parser->parse('"C:\\\\path\\\\to\\\\file"');

        $this->assertEquals('C:\path\to\file', $result['value']);
    }

    public function test_trims_whitespace_from_unquoted_values(): void
    {
        $result = $this->parser->parse('  spaced  ');

        $this->assertEquals('spaced', $result['value']);
        $this->assertFalse($result['is_quoted']);
    }

    public function test_handles_empty_value(): void
    {
        $result = $this->parser->parse('');

        $this->assertEquals('', $result['value']);
        $this->assertFalse($result['is_quoted']);
    }

    public function test_handles_empty_quoted_value(): void
    {
        $result = $this->parser->parse('""');

        $this->assertEquals('', $result['value']);
        $this->assertTrue($result['is_quoted']);
    }
}
