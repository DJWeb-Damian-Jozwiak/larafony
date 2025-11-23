<?php

declare(strict_types=1);

namespace Larafony\Console;

use Larafony\Framework\Console\Formatters\OutputFormatter;
use Larafony\Framework\Console\Formatters\Styles\DangerStyle;
use Larafony\Framework\Console\Formatters\Styles\InfoStyle;
use Larafony\Framework\Console\Formatters\Styles\SuccessStyle;
use Larafony\Framework\Console\Formatters\Styles\WarningStyle;
use Larafony\Framework\Console\Output;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\StreamInterface;

class OutputTest extends TestCase
{
    private ContainerContract $container;
    private StreamInterface $inputStream;
    private StreamInterface $outputStream;
    private OutputFormatter $formatter;
    private Output $consoleOutput;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerContract::class);
        $this->inputStream = $this->createMock(StreamInterface::class);
        $this->outputStream = $this->createMock(StreamInterface::class);

        // Use real formatter with real styles
        $this->formatter = new OutputFormatter($this->container);
        $this->formatter->withStyle('info', new InfoStyle());
        $this->formatter->withStyle('danger', new DangerStyle());
        $this->formatter->withStyle('warning', new WarningStyle());
        $this->formatter->withStyle('success', new SuccessStyle());

        $this->container->method('get')
            ->willReturnMap([
                ['input_stream', $this->inputStream],
                ['output_stream', $this->outputStream],
                [OutputFormatter::class, $this->formatter],
            ]);

        $this->consoleOutput = new Output($this->container);
    }

    public function testWrite(): void
    {
        $this->outputStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Test message'));

        $this->consoleOutput->write('Test message');
    }

    public function testWriteln(): void
    {
        $this->outputStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo("Test message" . PHP_EOL));

        $this->consoleOutput->writeln('Test message');
    }

    public function testInfo(): void
    {
        $this->outputStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo("\033[36mTest info\033[0m" . PHP_EOL));

        $this->consoleOutput->info('Test info');
    }

    public function testError(): void
    {
        $this->outputStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo("\033[31mTest error\033[0m" . PHP_EOL));

        $this->consoleOutput->error('Test error');
    }

    public function testWarning(): void
    {
        $this->outputStream->expects($this->once())
            ->method('write')
            ->with(
                $this->equalTo("\033[33mTest warning\033[0m" . PHP_EOL)
            );

        $this->consoleOutput->warning('Test warning');
    }

    public function testSuccess(): void
    {
        $this->outputStream->expects($this->once())
            ->method('write')
            ->with(
                $this->equalTo("\033[32mTest success\033[0m" . PHP_EOL)
            );

        $this->consoleOutput->success('Test success');
    }

    public function testQuestion(): void
    {
        $this->outputStream->expects($this->exactly(2))
            ->method('write')
            ->willReturnCallback(function ($arg) {
                static $callNumber = 0;
                $callNumber++;

                if ($callNumber === 1) {
                    $this->assertEquals(
                        "\033[36mTest question\033[0m" . PHP_EOL,
                        $arg
                    );
                } elseif ($callNumber === 2) {
                    $this->assertEquals(PHP_EOL, $arg);
                }

                return strlen($arg);
            });

        $this->inputStream->expects($this->once())
            ->method('read')
            ->with($this->equalTo(1024))
            ->willReturn("Test answer");

        $answer = $this->consoleOutput->question('Test question');
        $this->assertEquals('Test answer', $answer);
    }

    public function testSecret(): void
    {
        $this->inputStream->expects($this->once())
            ->method('read')
            ->with($this->equalTo(1024))
            ->willReturn("secret123");

        $password = $this->consoleOutput->secret('Enter password');
        $this->assertEquals('secret123', $password);
    }
}