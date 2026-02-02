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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Http\Message\StreamInterface;

class OutputTest extends TestCase
{
    private OutputFormatter $formatter;

    public function setUp(): void
    {
        $container = $this->createStub(ContainerContract::class);

        // Use real formatter with real styles
        $this->formatter = new OutputFormatter($container);
        $this->formatter->withStyle('info', new InfoStyle());
        $this->formatter->withStyle('danger', new DangerStyle());
        $this->formatter->withStyle('warning', new WarningStyle());
        $this->formatter->withStyle('success', new SuccessStyle());
    }

    private function createOutputWithMock(): array
    {
        $inputStream = $this->createStub(StreamInterface::class);
        $outputStream = $this->createMock(StreamInterface::class);

        $container = $this->createStub(ContainerContract::class);
        $container->method('get')
            ->willReturnMap([
                ['input_stream', $inputStream],
                ['output_stream', $outputStream],
                [OutputFormatter::class, $this->formatter],
            ]);

        $consoleOutput = new Output($container);

        return [$consoleOutput, $outputStream];
    }

    public function testWrite(): void
    {
        [$consoleOutput, $outputStream] = $this->createOutputWithMock();

        $outputStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Test message'));

        $consoleOutput->write('Test message');
    }

    public function testWriteln(): void
    {
        [$consoleOutput, $outputStream] = $this->createOutputWithMock();

        $outputStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo("Test message" . PHP_EOL));

        $consoleOutput->writeln('Test message');
    }

    public function testInfo(): void
    {
        [$consoleOutput, $outputStream] = $this->createOutputWithMock();

        $outputStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo("\033[36mTest info\033[0m" . PHP_EOL));

        $consoleOutput->info('Test info');
    }

    public function testError(): void
    {
        [$consoleOutput, $outputStream] = $this->createOutputWithMock();

        $outputStream->expects($this->once())
            ->method('write')
            ->with($this->equalTo("\033[31mTest error\033[0m" . PHP_EOL));

        $consoleOutput->error('Test error');
    }

    public function testWarning(): void
    {
        [$consoleOutput, $outputStream] = $this->createOutputWithMock();

        $outputStream->expects($this->once())
            ->method('write')
            ->with(
                $this->equalTo("\033[33mTest warning\033[0m" . PHP_EOL)
            );

        $consoleOutput->warning('Test warning');
    }

    public function testSuccess(): void
    {
        [$consoleOutput, $outputStream] = $this->createOutputWithMock();

        $outputStream->expects($this->once())
            ->method('write')
            ->with(
                $this->equalTo("\033[32mTest success\033[0m" . PHP_EOL)
            );

        $consoleOutput->success('Test success');
    }

    public function testQuestion(): void
    {
        // Create mock for inputStream since we need to verify its behavior
        $inputStream = $this->createMock(StreamInterface::class);
        $inputStream->expects($this->once())
            ->method('read')
            ->with($this->equalTo(1024))
            ->willReturn("Test answer");

        $outputStream = $this->createMock(StreamInterface::class);

        $container = $this->createStub(ContainerContract::class);
        $container->method('get')
            ->willReturnMap([
                ['input_stream', $inputStream],
                ['output_stream', $outputStream],
                [OutputFormatter::class, $this->formatter],
            ]);

        $output = new Output($container);

        $outputStream->expects($this->exactly(2))
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

        $answer = $output->question('Test question');
        $this->assertEquals('Test answer', $answer);
    }

    public function testSecret(): void
    {
        // Create mock for inputStream since we need to verify its behavior
        $inputStream = $this->createMock(StreamInterface::class);
        $inputStream->expects($this->once())
            ->method('read')
            ->with($this->equalTo(1024))
            ->willReturn("secret123");

        // Use a stub for outputStream since we don't need to verify calls
        $outputStream = $this->createStub(StreamInterface::class);

        $container = $this->createStub(ContainerContract::class);
        $container->method('get')
            ->willReturnMap([
                ['input_stream', $inputStream],
                ['output_stream', $outputStream],
                [OutputFormatter::class, $this->formatter],
            ]);

        $output = new Output($container);

        $password = $output->secret('Enter password');
        $this->assertEquals('secret123', $password);
    }
}
