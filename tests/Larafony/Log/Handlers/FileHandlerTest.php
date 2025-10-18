<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log\Handlers;

use Larafony\Framework\Enums\Log\LogLevel;
use Larafony\Framework\Log\Contracts\FormatterContract;
use Larafony\Framework\Log\Contracts\RotatorContract;
use Larafony\Framework\Log\Context;
use Larafony\Framework\Log\Handlers\FileHandler;
use Larafony\Framework\Log\Message;
use Larafony\Framework\Log\Metadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileHandler::class)]
final class FileHandlerTest extends TestCase
{
    private string $tempDir;
    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/larafony_log_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->logPath = $this->tempDir . '/test.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    #[Test]
    public function it_creates_log_file_if_not_exists(): void
    {
        $formatter = $this->createMock(FormatterContract::class);
        $formatter->method('format')->willReturn('Log entry');

        $handler = new FileHandler($this->logPath, $formatter);
        $message = new Message(LogLevel::INFO, 'Test', new Context());

        $handler->handle($message);

        $this->assertFileExists($this->logPath);
    }

    #[Test]
    public function it_writes_formatted_message_to_file(): void
    {
        $formatter = $this->createMock(FormatterContract::class);
        $formatter->method('format')->willReturn('Formatted log entry');

        $handler = new FileHandler($this->logPath, $formatter);
        $message = new Message(LogLevel::INFO, 'Test', new Context());

        $handler->handle($message);

        $content = file_get_contents($this->logPath);
        $this->assertSame('Formatted log entry', $content);
    }

    #[Test]
    public function it_appends_to_existing_file(): void
    {
        $formatter = $this->createMock(FormatterContract::class);
        $formatter->method('format')->willReturnOnConsecutiveCalls('Entry 1', 'Entry 2');

        $handler = new FileHandler($this->logPath, $formatter);

        $handler->handle(new Message(LogLevel::INFO, 'First', new Context()));
        $handler->handle(new Message(LogLevel::INFO, 'Second', new Context()));

        $content = file_get_contents($this->logPath);
        $this->assertSame('Entry 1Entry 2', $content);
    }

    #[Test]
    public function it_creates_directory_if_not_exists(): void
    {
        $nestedPath = $this->tempDir . '/nested/dir/test.log';
        $formatter = $this->createMock(FormatterContract::class);
        $formatter->method('format')->willReturn('Log entry');

        $handler = new FileHandler($nestedPath, $formatter);
        $message = new Message(LogLevel::INFO, 'Test', new Context());

        $handler->handle($message);

        $this->assertFileExists($nestedPath);
        $this->assertDirectoryExists(dirname($nestedPath));

        // Cleanup
        unlink($nestedPath);
        rmdir($this->tempDir . '/nested/dir');
        rmdir($this->tempDir . '/nested');
    }

    #[Test]
    public function it_checks_rotation_before_writing(): void
    {
        $formatter = $this->createMock(FormatterContract::class);
        $formatter->method('format')->willReturn('Entry');

        $rotator = $this->createMock(RotatorContract::class);
        $rotator->expects($this->once())
            ->method('shouldRotate')
            ->with($this->logPath)
            ->willReturn(false);

        $handler = new FileHandler($this->logPath, $formatter, $rotator);
        $handler->handle(new Message(LogLevel::INFO, 'Test', new Context()));
    }

    #[Test]
    public function it_rotates_file_when_needed(): void
    {
        file_put_contents($this->logPath, 'Old content');

        $formatter = $this->createMock(FormatterContract::class);
        $formatter->method('format')->willReturn('New entry');

        $rotatedPath = $this->logPath . '.old';
        $rotator = $this->createMock(RotatorContract::class);
        $rotator->method('shouldRotate')->willReturn(true);
        $rotator->method('rotate')->willReturn($rotatedPath);
        $rotator->expects($this->once())->method('cleanup');

        $handler = new FileHandler($this->logPath, $formatter, $rotator);
        $handler->handle(new Message(LogLevel::INFO, 'Test', new Context()));

        $this->assertFileExists($this->logPath);

        // Cleanup
        if (file_exists($rotatedPath)) {
            unlink($rotatedPath);
        }
    }

    #[Test]
    public function it_sets_file_permissions(): void
    {
        $formatter = $this->createMock(FormatterContract::class);
        $formatter->method('format')->willReturn('Entry');

        $handler = new FileHandler($this->logPath, $formatter);
        $handler->handle(new Message(LogLevel::INFO, 'Test', new Context()));

        $perms = fileperms($this->logPath) & 0777;
        $this->assertSame(0644, $perms);
    }
}
