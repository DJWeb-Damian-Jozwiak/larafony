<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log\Formatters;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Enums\Log\LogLevel;
use Larafony\Framework\Log\Context;
use Larafony\Framework\Log\Formatters\TextFormatter;
use Larafony\Framework\Log\Message;
use Larafony\Framework\Log\Metadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextFormatter::class)]
final class TextFormatterTest extends TestCase
{
    private TextFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new TextFormatter();
        ClockFactory::freeze('2024-01-15 10:30:45');
    }

    protected function tearDown(): void
    {
        ClockFactory::reset();
        parent::tearDown();
    }

    #[Test]
    public function it_formats_message_as_text(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test message',
            new Context(),
            Metadata::create()
        );

        $result = $this->formatter->format($message);

        $this->assertIsString($result);
        $this->assertStringContainsString('Test message', $result);
    }

    #[Test]
    public function it_includes_timestamp_in_output(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context()
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString('[2024-01-15 10:30:45]', $result);
    }

    #[Test]
    public function it_includes_level_in_output(): void
    {
        $message = new Message(
            LogLevel::ERROR,
            'Error occurred',
            new Context()
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString('ERROR:', $result);
    }

    #[Test]
    public function it_includes_message_in_output(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'User logged in',
            new Context()
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString('User logged in', $result);
    }

    #[Test]
    public function it_includes_context_as_json(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context(['user' => 'john', 'id' => 123])
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString('Context:', $result);
        $this->assertStringContainsString('{"user":"john","id":123}', $result);
    }

    #[Test]
    public function it_includes_metadata_as_json(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context(),
            Metadata::create()
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString('Metadata:', $result);
        $this->assertStringContainsString('"timestamp":"2024-01-15 10:30:45"', $result);
    }

    #[Test]
    public function it_ends_with_newline(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context()
        );

        $result = $this->formatter->format($message);

        $this->assertStringEndsWith("\n", $result);
    }

    #[Test]
    public function it_converts_to_array_with_all_fields(): void
    {
        $message = new Message(
            LogLevel::WARNING,
            'Warning message',
            new Context(['key' => 'value']),
            Metadata::create()
        );

        $array = $this->formatter->toArray($message);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('datetime', $array);
        $this->assertArrayHasKey('level', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('context', $array);
        $this->assertArrayHasKey('metadata', $array);
    }

    #[Test]
    public function it_formats_complete_log_line(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'User action',
            new Context(['user' => 'john'])
        );

        $result = $this->formatter->format($message);

        $expected = '[2024-01-15 10:30:45] INFO: User action Context: {"user":"john"} Metadata: []';
        $this->assertStringContainsString($expected, $result);
    }
}
