<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Input;

use Larafony\Framework\Console\Input\InputParser;
use PHPUnit\Framework\TestCase;

final class InputParserTest extends TestCase
{
    private InputParser $parser;

    protected function setUp(): void
    {
        $this->parser = new InputParser();
    }

    public function testParsesCommandName(): void
    {
        $input = $this->parser->parse(['bin/console', 'greet']);

        $this->assertSame('greet', $input->command);
    }

    public function testParsesPositionalArguments(): void
    {
        $input = $this->parser->parse(['bin/console', 'greet', 'Alice', 'Bob']);

        $this->assertSame('Alice', $input->arguments[0]);
        $this->assertSame('Bob', $input->arguments[1]);
    }

    public function testParsesOptions(): void
    {
        $input = $this->parser->parse(['bin/console', 'greet', '--greeting', '--shout']);

        $this->assertSame('greeting', $input->options['greeting']);
        $this->assertSame('shout', $input->options['shout']);
    }

    public function testSeparatesArgumentsFromOptions(): void
    {
        $input = $this->parser->parse(['bin/console', 'test', 'arg1', '--option1', 'arg2', '--option2']);

        // array_filter preserves keys, so args will have keys 0 and 2
        $this->assertCount(2, $input->arguments);
        $this->assertSame('arg1', $input->arguments[0]);
        $this->assertSame('arg2', $input->arguments[2]); // Key 2, not 1

        $this->assertCount(2, $input->options);
        $this->assertTrue($input->hasOption('option1'));
        $this->assertTrue($input->hasOption('option2'));
    }

    public function testHandlesEmptyArgv(): void
    {
        $input = $this->parser->parse(['bin/console']);

        $this->assertSame('', $input->command);
        $this->assertEmpty($input->arguments);
        $this->assertEmpty($input->options);
    }

    public function testStripsDoubleHyphensFromOptions(): void
    {
        $input = $this->parser->parse(['bin/console', 'test', '--verbose', '--force']);

        $this->assertArrayHasKey('verbose', $input->options);
        $this->assertArrayHasKey('force', $input->options);
        $this->assertArrayNotHasKey('--verbose', $input->options);
    }

    public function testHandlesOnlyOptions(): void
    {
        $input = $this->parser->parse(['bin/console', 'test', '--opt1', '--opt2', '--opt3']);

        $this->assertEmpty($input->arguments);
        $this->assertCount(3, $input->options);
    }

    public function testHandlesOnlyArguments(): void
    {
        $input = $this->parser->parse(['bin/console', 'test', 'arg1', 'arg2', 'arg3']);

        $this->assertCount(3, $input->arguments);
        $this->assertEmpty($input->options);
    }
}
