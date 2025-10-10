<?php

declare(strict_types=1);

namespace Larafony\Tests\Config\Environment\Parser;

use Larafony\Framework\Config\Environment\Exception\ParseError;
use PHPUnit\Framework\TestCase;
use Larafony\Framework\Config\Environment\Parser\LineParser;
use Larafony\Framework\Config\Environment\Dto\LineType;

class LineParserTest extends TestCase
{
    private LineParser $parser;

    protected function setUp(): void
    {
        $this->parser = new LineParser();
    }

    public function test_parses_simple_variable(): void
    {
        $result = $this->parser->parse('APP_NAME=Larafony', 1);

        $this->assertTrue($result->isVariable);
        $this->assertEquals('APP_NAME', $result->variable->key);
        $this->assertEquals('Larafony', $result->variable->value);
        $this->assertEquals(1, $result->lineNumber);
    }

    public function test_parses_quoted_variable(): void
    {
        $result = $this->parser->parse('APP_NAME="Larafony Framework"', 1);

        $this->assertTrue($result->isVariable);
        $this->assertEquals('APP_NAME', $result->variable->key);
        $this->assertEquals('Larafony Framework', $result->variable->value);
        $this->assertTrue($result->variable->isQuoted);
    }

    public function test_handles_spaces_around_equals(): void
    {
        $result = $this->parser->parse('KEY = value', 1);

        $this->assertTrue($result->isVariable);
        $this->assertEquals('KEY', $result->variable->key);
        $this->assertEquals('value', $result->variable->value);
    }

    public function test_recognizes_comment_lines(): void
    {
        $result = $this->parser->parse('# This is a comment', 1);

        $this->assertTrue($result->isComment);
        $this->assertFalse($result->isVariable);
    }

    public function test_recognizes_empty_lines(): void
    {
        $result = $this->parser->parse('   ', 1);

        $this->assertTrue($result->isEmpty);
        $this->assertFalse($result->isVariable);
    }

    public function test_handles_empty_value(): void
    {
        $result = $this->parser->parse('EMPTY=', 1);

        $this->assertTrue($result->isVariable);
        $this->assertEquals('EMPTY', $result->variable->key);
        $this->assertEquals('', $result->variable->value);
    }

    public function test_throws_exception_on_invalid_key_format(): void
    {
        $this->expectException(ParseError::class);
        //$this->expectExceptionMessage('Invalid syntax at line 1');

        $this->parser->parse('123invalid=value');
    }

    public function test_throws_exception_on_missing_equals(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse('INVALID_NO_EQUALS');
    }

    public function test_allows_underscores_in_keys(): void
    {
        $result = $this->parser->parse('MY_CUSTOM_KEY=value');

        $this->assertEquals('MY_CUSTOM_KEY', $result->variable->key);
    }

    public function test_allows_numbers_in_keys(): void
    {
        $result = $this->parser->parse('KEY_123=value');

        $this->assertEquals('KEY_123', $result->variable->key);
    }
}
