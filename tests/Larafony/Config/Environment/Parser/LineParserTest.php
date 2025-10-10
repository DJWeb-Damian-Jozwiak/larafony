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

    public function testParsesSimpleVariable(): void
    {
        $result = $this->parser->parse('APP_NAME=Larafony', 1);

        $this->assertTrue($result->isVariable);
        $this->assertEquals('APP_NAME', $result->variable->key);
        $this->assertEquals('Larafony', $result->variable->value);
        $this->assertEquals(1, $result->lineNumber);
    }

    public function testParsesQuotedVariable(): void
    {
        $result = $this->parser->parse('APP_NAME="Larafony Framework"', 1);

        $this->assertTrue($result->isVariable);
        $this->assertEquals('APP_NAME', $result->variable->key);
        $this->assertEquals('Larafony Framework', $result->variable->value);
        $this->assertTrue($result->variable->isQuoted);
    }

    public function testHandlesSpacesAroundEquals(): void
    {
        $result = $this->parser->parse('KEY = value', 1);

        $this->assertTrue($result->isVariable);
        $this->assertEquals('KEY', $result->variable->key);
        $this->assertEquals('value', $result->variable->value);
    }

    public function testRecognizesCommentLines(): void
    {
        $result = $this->parser->parse('# This is a comment', 1);

        $this->assertTrue($result->isComment);
        $this->assertFalse($result->isVariable);
    }

    public function testRecognizesEmptyLines(): void
    {
        $result = $this->parser->parse('   ', 1);

        $this->assertTrue($result->isEmpty);
        $this->assertFalse($result->isVariable);
    }

    public function testHandlesEmptyValue(): void
    {
        $result = $this->parser->parse('EMPTY=', 1);

        $this->assertTrue($result->isVariable);
        $this->assertEquals('EMPTY', $result->variable->key);
        $this->assertEquals('', $result->variable->value);
    }

    public function testThrowsExceptionOnInvalidKeyFormat(): void
    {
        $this->expectException(ParseError::class);
        //$this->expectExceptionMessage('Invalid syntax at line 1');

        $this->parser->parse('123invalid=value');
    }

    public function testThrowsExceptionOnMissingEquals(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse('INVALID_NO_EQUALS');
    }

    public function testAllowsUnderscoresInKeys(): void
    {
        $result = $this->parser->parse('MY_CUSTOM_KEY=value');

        $this->assertEquals('MY_CUSTOM_KEY', $result->variable->key);
    }

    public function testAllowsNumbersInKeys(): void
    {
        $result = $this->parser->parse('KEY_123=value');

        $this->assertEquals('KEY_123', $result->variable->key);
    }
}
