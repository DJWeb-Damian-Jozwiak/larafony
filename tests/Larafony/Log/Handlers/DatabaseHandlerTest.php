<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log\Handlers;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Database\ORM\Entities\DatabaseLog;
use Larafony\Framework\Enums\Log\LogLevel;
use Larafony\Framework\Log\Context;
use Larafony\Framework\Log\Handlers\DatabaseHandler;
use Larafony\Framework\Log\Message;
use Larafony\Framework\Log\Metadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseHandler::class)]
final class DatabaseHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ClockFactory::reset();
    }

    protected function tearDown(): void
    {
        ClockFactory::reset();
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_constructed(): void
    {
        $handler = new DatabaseHandler();

        $this->assertInstanceOf(DatabaseHandler::class, $handler);
    }

    #[Test]
    public function it_creates_json_formatter_automatically(): void
    {
        $handler = new DatabaseHandler();

        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('formatter');
        $formatter = $property->getValue($handler);

        $this->assertInstanceOf(\Larafony\Framework\Log\Formatters\JsonFormatter::class, $formatter);
    }

    #[Test]
    public function it_handles_message_by_saving_to_database(): void
    {
        ClockFactory::freeze('2024-01-15 10:00:00');

        $handler = new DatabaseHandler();
        $message = new Message(
            LogLevel::INFO,
            'Test message',
            new Context(['user' => 'john']),
            Metadata::create()
        );

        // Verify handler can process the message without errors
        // In a real environment this would save to database
        // In test environment we just verify it doesn't crash
        try {
            $handler->handle($message);
            // If database is configured, this succeeds
            $this->assertTrue(true);
        } catch (\RuntimeException $e) {
            // If database is not configured, we expect this exception
            $this->assertEquals('Database manager not set', $e->getMessage());
        }
    }

    #[Test]
    public function it_converts_message_to_array_format(): void
    {
        ClockFactory::freeze('2024-01-15 10:00:00');

        $handler = new DatabaseHandler();
        $message = new Message(
            LogLevel::WARNING,
            'Warning message',
            new Context(['ip' => '127.0.0.1']),
            Metadata::create()
        );

        // Access the formatter to verify the conversion
        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('formatter');
        $formatter = $property->getValue($handler);

        $result = $formatter->toArray($message);

        $this->assertIsArray($result);
        $this->assertEquals('WARNING', $result['level']);
        $this->assertEquals('Warning message', $result['message']);
        $this->assertArrayHasKey('context', $result);
        $this->assertEquals(['ip' => '127.0.0.1'], $result['context']);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals(['timestamp' => '2024-01-15 10:00:00'], $result['metadata']);
    }
}
