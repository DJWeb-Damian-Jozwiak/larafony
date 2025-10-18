<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log;

use Larafony\Framework\Enums\Log\LogLevel;
use Larafony\Framework\Log\Context;
use Larafony\Framework\Log\Message;
use Larafony\Framework\Log\Metadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Message::class)]
final class MessageTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_enum_level(): void
    {
        $level = LogLevel::INFO;
        $text = 'Test message';
        $context = new Context(['user' => 'John']);
        $metadata = Metadata::create();

        $message = new Message($level, $text, $context, $metadata);

        $this->assertSame($level, $message->level);
        $this->assertSame($text, $message->message);
        $this->assertSame($context, $message->context);
        $this->assertSame($metadata, $message->metadata);
    }

    #[Test]
    public function it_can_be_created_with_string_level(): void
    {
        $level = 'debug';
        $text = 'Debug message';
        $context = new Context();

        $message = new Message($level, $text, $context);

        $this->assertSame($level, $message->level);
        $this->assertSame($text, $message->message);
    }

    #[Test]
    public function it_can_be_created_without_metadata(): void
    {
        $message = new Message(
            LogLevel::ERROR,
            'Error occurred',
            new Context()
        );

        $this->assertNull($message->metadata);
    }

    #[Test]
    public function it_is_readonly(): void
    {
        $message = new Message(
            LogLevel::WARNING,
            'Warning message',
            new Context()
        );

        $this->expectException(\Error::class);
        $message->level = LogLevel::ERROR;
    }

    #[Test]
    public function it_stores_all_log_level_types(): void
    {
        $levels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];

        foreach ($levels as $level) {
            $message = new Message($level, 'Test', new Context());
            $this->assertSame($level, $message->level);
        }
    }
}
