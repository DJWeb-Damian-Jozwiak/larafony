<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log\Formatters;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Enums\Log\LogLevel;
use Larafony\Framework\Log\Context;
use Larafony\Framework\Log\Formatters\JsonFormatter;
use Larafony\Framework\Log\Message;
use Larafony\Framework\Log\Metadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonFormatter::class)]
final class JsonFormatterTest extends TestCase
{
    private JsonFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new JsonFormatter();
        ClockFactory::freeze('2024-01-15 10:30:45');
    }

    protected function tearDown(): void
    {
        ClockFactory::reset();
        parent::tearDown();
    }

    #[Test]
    public function it_formats_message_as_json(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test message',
            new Context(['user' => 'john']),
            Metadata::create()
        );

        $result = $this->formatter->format($message);

        $this->assertJson($result);
    }

    #[Test]
    public function it_includes_level_in_output(): void
    {
        $message = new Message(
            LogLevel::ERROR,
            'Error occurred',
            new Context(),
            Metadata::create()
        );

        $result = $this->formatter->format($message);
        $decoded = json_decode($result, true);

        $this->assertSame('ERROR', $decoded['level']);
    }

    #[Test]
    public function it_includes_message_in_output(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test message',
            new Context(),
            Metadata::create()
        );

        $result = $this->formatter->format($message);
        $decoded = json_decode($result, true);

        $this->assertSame('Test message', $decoded['message']);
    }

    #[Test]
    public function it_includes_context_in_output(): void
    {
        $context = ['user_id' => 123, 'action' => 'login'];
        $message = new Message(
            LogLevel::INFO,
            'User logged in',
            new Context($context),
            Metadata::create()
        );

        $result = $this->formatter->format($message);
        $decoded = json_decode($result, true);

        $this->assertSame($context, $decoded['context']);
    }

    #[Test]
    public function it_includes_metadata_in_output(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context(),
            Metadata::create()
        );

        $result = $this->formatter->format($message);
        $decoded = json_decode($result, true);

        $this->assertArrayHasKey('metadata', $decoded);
        $this->assertArrayHasKey('timestamp', $decoded['metadata']);
        $this->assertSame('2024-01-15 10:30:45', $decoded['metadata']['timestamp']);
    }

    #[Test]
    public function it_filters_empty_values(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context()
        );

        $array = $this->formatter->toArray($message);

        $this->assertArrayNotHasKey('metadata', $array);
    }

    #[Test]
    public function it_converts_to_array_correctly(): void
    {
        $message = new Message(
            LogLevel::WARNING,
            'Warning message',
            new Context(['key' => 'value']),
            Metadata::create()
        );

        $array = $this->formatter->toArray($message);

        $this->assertIsArray($array);
        $this->assertSame('WARNING', $array['level']);
        $this->assertSame('Warning message', $array['message']);
        $this->assertSame(['key' => 'value'], $array['context']);
    }

    #[Test]
    public function it_handles_string_level(): void
    {
        $message = new Message(
            'custom_level',
            'Custom message',
            new Context()
        );

        $array = $this->formatter->toArray($message);

        $this->assertSame('custom_level', $array['level']);
    }

    #[Test]
    public function it_produces_pretty_printed_json(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context(['key' => 'value'])
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString("\n", $result);
        $this->assertStringContainsString('    ', $result);
    }
}
