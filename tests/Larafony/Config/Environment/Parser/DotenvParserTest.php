<?php

declare(strict_types=1);

namespace Larafony\Tests\Config\Environment\Parser;

use PHPUnit\Framework\TestCase;
use Larafony\Framework\Config\Environment\Parser\DotenvParser;
use Larafony\Framework\Config\Environment\Exception\ParseException;

class DotenvParserTest extends TestCase
{
    private DotenvParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DotenvParser();
    }

    public function testParsesSimpleKeyValue(): void
    {
        $content = "APP_NAME=Larafony";
        $result = $this->parser->parse($content);

        $this->assertTrue($result->has('APP_NAME'));
        $this->assertEquals('Larafony', $result->get('APP_NAME'));
        $this->assertEquals(1, $result->count());
    }

    public function testParsesMultipleVariables(): void
    {
        $content = <<<ENV
        APP_NAME=Larafony
        APP_DEBUG=true
        APP_ENV=local
        ENV;

        $result = $this->parser->parse($content);

        $this->assertEquals(3, $result->count());
        $this->assertEquals('Larafony', $result->get('APP_NAME'));
        $this->assertEquals('true', $result->get('APP_DEBUG'));
        $this->assertEquals('local', $result->get('APP_ENV'));
    }

    public function testParsesQuotedValues(): void
    {
        $content = <<<ENV
        APP_NAME="Larafony Framework"
        APP_MOTTO='Build it yourself'
        ENV;

        $result = $this->parser->parse($content);

        $this->assertEquals('Larafony Framework', $result->get('APP_NAME'));
        $this->assertEquals('Build it yourself', $result->get('APP_MOTTO'));
    }

    public function testHandlesEscapeSequencesInDoubleQuotes(): void
    {
        $content = 'MESSAGE="Line 1\nLine 2\tTabbed"';
        $result = $this->parser->parse($content);

        $expected = "Line 1\nLine 2\tTabbed";
        $this->assertEquals($expected, $result->get('MESSAGE'));
    }

    public function testIgnoresComments(): void
    {
        $content = <<<ENV
        # This is a comment
        APP_NAME=Larafony
        # Another comment
        APP_DEBUG=true
        ENV;

        $result = $this->parser->parse($content);

        $this->assertEquals(2, $result->count());
        $this->assertEquals('Larafony', $result->get('APP_NAME'));
    }


    public function testThrowsExceptionOnInvalidSyntax(): void
    {
        $this->expectException(\Throwable::class);

        $content = "invalid syntax without equals";
        $this->parser->parse($content);
    }

    public function testPreservesLineNumbers(): void
    {
        $content = <<<ENV

        # Comment
        APP_NAME=Larafony

        APP_DEBUG=true
        ENV;

        $result = $this->parser->parse($content);

        $var1 = $result->variables['APP_NAME'];
        $var2 = $result->variables['APP_DEBUG'];

        $this->assertEquals(3, $var1->lineNumber);
        $this->assertEquals(5, $var2->lineNumber);
    }

    public function testHandlesEmptyValues(): void
    {
        $content = "EMPTY_VAR=";
        $result = $this->parser->parse($content);

        $this->assertEquals('', $result->get('EMPTY_VAR'));
        $this->assertTrue($result->has('EMPTY_VAR'));
    }

    public function testHandlesSpacesAroundEquals(): void
    {
        $content = <<<ENV
        KEY1 = value1
        KEY2= value2
        KEY3 =value3
        ENV;

        $result = $this->parser->parse($content);

        $this->assertEquals('value1', $result->get('KEY1'));
        $this->assertEquals('value2', $result->get('KEY2'));
        $this->assertEquals('value3', $result->get('KEY3'));
    }

    public function testToArrayReturnsSimpleKeyValuePairs(): void
    {
        $content = <<<ENV
        KEY1=value1
        KEY2=value2
        ENV;

        $result = $this->parser->parse($content);
        $array = $result->toArray();

        $this->assertEquals([
            'KEY1' => 'value1',
            'KEY2' => 'value2',
        ], $array);
    }

    public function testTracksTotalLines(): void
    {
        $content = <<<ENV
        KEY1=value1
        # comment

        KEY2=value2
        ENV;

        $result = $this->parser->parse($content);

        $this->assertEquals(4, $result->totalLines);
    }
}
