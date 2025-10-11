<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Input;

use Larafony\Framework\Console\Input\Input;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    public function testCreatesInputWithCommand(): void
    {
        $input = new Input('greet');

        $this->assertSame('greet', $input->command);
    }

    public function testStoresArguments(): void
    {
        $input = new Input('greet', ['Alice', 'Bob']);

        $this->assertSame('Alice', $input->arguments[0]);
        $this->assertSame('Bob', $input->arguments[1]);
    }

    public function testStoresOptionsWithoutDoubleHyphens(): void
    {
        $input = new Input('greet', [], ['--verbose', '--force']);

        $this->assertArrayHasKey('verbose', $input->options);
        $this->assertArrayHasKey('force', $input->options);
    }

    public function testHasArgumentReturnsTrueWhenArgumentExists(): void
    {
        $input = new Input('test', ['value']);

        $this->assertTrue($input->hasArgument('0'));
    }

    public function testHasArgumentReturnsFalseWhenArgumentDoesNotExist(): void
    {
        $input = new Input('test');

        $this->assertFalse($input->hasArgument('0'));
    }

    public function testGetArgumentReturnsArgumentValue(): void
    {
        $input = new Input('test', ['Alice', 'Bob']);

        $this->assertSame('Alice', $input->getArgument(0));
        $this->assertSame('Bob', $input->getArgument(1));
    }

    public function testGetArgumentReturnsNullWhenNotFound(): void
    {
        $input = new Input('test');

        $this->assertNull($input->getArgument(0));
    }

    public function testHasOptionReturnsTrueWhenOptionExists(): void
    {
        $input = new Input('test', [], ['--verbose']);

        $this->assertTrue($input->hasOption('verbose'));
    }

    public function testHasOptionReturnsFalseWhenOptionDoesNotExist(): void
    {
        $input = new Input('test');

        $this->assertFalse($input->hasOption('verbose'));
    }

    public function testGetOptionReturnsOptionValue(): void
    {
        $input = new Input('test', [], ['--verbose', '--force']);

        $this->assertSame('verbose', $input->getOption('verbose'));
        $this->assertSame('force', $input->getOption('force'));
    }

    public function testGetOptionReturnsNullWhenNotFound(): void
    {
        $input = new Input('test');

        $this->assertNull($input->getOption('verbose'));
    }
}
