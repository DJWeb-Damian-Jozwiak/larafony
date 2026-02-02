<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log;

use Larafony\Framework\Enums\Log\LogLevel;
use Larafony\Framework\Log\Contracts\HandlerContract;
use Larafony\Framework\Log\Logger;
use Larafony\Framework\Log\Message;
use Larafony\Framework\Log\PlaceholderProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Logger::class)]
final class LoggerTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_handlers(): void
    {
        $handler = $this->createStub(HandlerContract::class);
        $logger = new Logger([$handler]);

        $this->assertCount(1, $logger->handlers);
    }

    #[Test]
    public function it_filters_out_non_handler_objects(): void
    {
        $handler = $this->createStub(HandlerContract::class);
        $invalid = new \stdClass();

        $logger = new Logger([$handler, $invalid]);

        $this->assertCount(1, $logger->handlers);
    }

    #[Test]
    public function it_creates_placeholder_processor_if_not_provided(): void
    {
        $logger = new Logger([]);

        $reflection = new \ReflectionClass($logger);
        $property = $reflection->getProperty('placeholderProcessor');
        $processor = $property->getValue($logger);

        $this->assertInstanceOf(PlaceholderProcessor::class, $processor);
    }

    #[Test]
    public function it_calls_handlers_when_logging(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (Message $message) {
                return $message->message === 'Test message'
                    && $message->level === 'info';
            }));

        $logger = new Logger([$handler]);
        $logger->info('Test message');
    }

    #[Test]
    public function it_processes_placeholders_in_messages(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (Message $message) {
                return $message->message === 'User john logged in';
            }));

        $logger = new Logger([$handler]);
        $logger->info('User {username} logged in', ['username' => 'john']);
    }

    #[Test]
    public function it_logs_emergency_messages(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(fn (Message $m) => $m->level === 'emergency'));

        $logger = new Logger([$handler]);
        $logger->emergency('Emergency message');
    }

    #[Test]
    public function it_logs_alert_messages(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(fn (Message $m) => $m->level === 'alert'));

        $logger = new Logger([$handler]);
        $logger->alert('Alert message');
    }

    #[Test]
    public function it_logs_critical_messages(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(fn (Message $m) => $m->level === 'critical'));

        $logger = new Logger([$handler]);
        $logger->critical('Critical message');
    }

    #[Test]
    public function it_logs_error_messages(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(fn (Message $m) => $m->level === 'error'));

        $logger = new Logger([$handler]);
        $logger->error('Error message');
    }

    #[Test]
    public function it_logs_warning_messages(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(fn (Message $m) => $m->level === 'warning'));

        $logger = new Logger([$handler]);
        $logger->warning('Warning message');
    }

    #[Test]
    public function it_logs_notice_messages(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(fn (Message $m) => $m->level === 'notice'));

        $logger = new Logger([$handler]);
        $logger->notice('Notice message');
    }

    #[Test]
    public function it_logs_info_messages(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(fn (Message $m) => $m->level === 'info'));

        $logger = new Logger([$handler]);
        $logger->info('Info message');
    }

    #[Test]
    public function it_logs_debug_messages(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(fn (Message $m) => $m->level === 'debug'));

        $logger = new Logger([$handler]);
        $logger->debug('Debug message');
    }

    #[Test]
    public function it_calls_multiple_handlers(): void
    {
        $handler1 = $this->createMock(HandlerContract::class);
        $handler1->expects($this->once())->method('handle');

        $handler2 = $this->createMock(HandlerContract::class);
        $handler2->expects($this->once())->method('handle');

        $logger = new Logger([$handler1, $handler2]);
        $logger->info('Test message');
    }

    #[Test]
    public function it_handles_stringable_messages(): void
    {
        $stringable = new class {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(fn (Message $m) => $m->message === 'Stringable message'));

        $logger = new Logger([$handler]);
        $logger->info($stringable);
    }

    #[Test]
    public function it_passes_context_to_message(): void
    {
        $handler = $this->createMock(HandlerContract::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (Message $message) {
                return $message->context->get('user_id') === 123
                    && $message->context->get('action') === 'login';
            }));

        $logger = new Logger([$handler]);
        $logger->info('User logged in', ['user_id' => 123, 'action' => 'login']);
    }
}
