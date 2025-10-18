<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log;

use Larafony\Framework\Log\Log;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(Log::class)]
final class LogTest extends TestCase
{
    private LoggerInterface $mockLogger;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static logger
        $this->resetLoggerInstance();

        $this->mockLogger = $this->createMock(LoggerInterface::class);

        // Create application instance and bind logger
        $this->app = Application::instance(base_path: '/tmp/test');
        $this->app->set(LoggerInterface::class, $this->mockLogger);
    }

    protected function tearDown(): void
    {
        $this->resetLoggerInstance();
        Application::empty();
        parent::tearDown();
    }

    private function resetLoggerInstance(): void
    {
        $reflection = new \ReflectionClass(Log::class);
        $property = $reflection->getProperty('logger');
        if ($property->isInitialized()) {
            $property->setValue(null, null);
        }
    }

    #[Test]
    public function it_logs_emergency_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('emergency')
            ->with('Emergency message', ['context' => 'data']);

        Log::emergency('Emergency message', ['context' => 'data']);
    }

    #[Test]
    public function it_logs_alert_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('alert')
            ->with('Alert message', []);

        Log::alert('Alert message');
    }

    #[Test]
    public function it_logs_critical_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('critical')
            ->with('Critical message', ['key' => 'value']);

        Log::critical('Critical message', ['key' => 'value']);
    }

    #[Test]
    public function it_logs_error_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with('Error message', []);

        Log::error('Error message');
    }

    #[Test]
    public function it_logs_warning_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with('Warning message', ['user_id' => 123]);

        Log::warning('Warning message', ['user_id' => 123]);
    }

    #[Test]
    public function it_logs_notice_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('notice')
            ->with('Notice message', []);

        Log::notice('Notice message');
    }

    #[Test]
    public function it_logs_info_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Info message', ['action' => 'login']);

        Log::info('Info message', ['action' => 'login']);
    }

    #[Test]
    public function it_logs_debug_messages(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('debug')
            ->with('Debug message', ['trace' => 'data']);

        Log::debug('Debug message', ['trace' => 'data']);
    }

    #[Test]
    public function it_reuses_same_logger_instance(): void
    {
        // First call initializes logger
        Log::info('First message');

        // Second call should reuse the same logger
        $this->mockLogger->expects($this->once())
            ->method('debug')
            ->with('Second message', []);

        Log::debug('Second message');
    }

    #[Test]
    public function it_passes_empty_context_by_default(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with('Message', []);

        Log::info('Message');
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

        Log::info('User logged in', $context);
    }
}
