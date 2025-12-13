<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Log\Log;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(Log::class)]
final class LogTest extends TestCase
{
    private LoggerInterface $mockLogger;
    private Log $log;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $container = $this->createMock(ContainerContract::class);
        $container->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($this->mockLogger);

        $this->log = new Log($container);
    }

    #[Test]
    public function it_logs_emergency_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('emergency')
            ->with('Emergency message', ['context' => 'data']);

        $this->log->emergency('Emergency message', ['context' => 'data']);
    }

    #[Test]
    public function it_logs_alert_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('alert')
            ->with('Alert message', []);

        $this->log->alert('Alert message');
    }

    #[Test]
    public function it_logs_critical_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('critical')
            ->with('Critical message', ['key' => 'value']);

        $this->log->critical('Critical message', ['key' => 'value']);
    }

    #[Test]
    public function it_logs_error_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with('Error message', []);

        $this->log->error('Error message');
    }

    #[Test]
    public function it_logs_warning_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with('Warning message', ['user_id' => 123]);

        $this->log->warning('Warning message', ['user_id' => 123]);
    }

    #[Test]
    public function it_logs_notice_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('notice')
            ->with('Notice message', []);

        $this->log->notice('Notice message');
    }

    #[Test]
    public function it_logs_info_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Info message', ['action' => 'login']);

        $this->log->info('Info message', ['action' => 'login']);
    }

    #[Test]
    public function it_logs_debug_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('debug')
            ->with('Debug message', ['trace' => 'data']);

        $this->log->debug('Debug message', ['trace' => 'data']);
    }

    #[Test]
    public function it_passes_empty_context_by_default(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Message', []);

        $this->log->info('Message');
    }

    #[Test]
    public function it_passes_context_array(): void
    {
        $context = [
            'user_id' => 123,
            'ip' => '127.0.0.1',
            'action' => 'login',
        ];

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('User logged in', $context);

        $this->log->info('User logged in', $context);
    }
}
