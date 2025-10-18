<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log\Formatters;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Enums\Log\LogLevel;
use Larafony\Framework\Log\Context;
use Larafony\Framework\Log\Formatters\XmlFormatter;
use Larafony\Framework\Log\Message;
use Larafony\Framework\Log\Metadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlFormatter::class)]
final class XmlFormatterTest extends TestCase
{
    private XmlFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new XmlFormatter();
        ClockFactory::freeze('2024-01-15 10:30:45');
    }

    protected function tearDown(): void
    {
        ClockFactory::reset();
        parent::tearDown();
    }

    #[Test]
    public function it_formats_message_as_xml(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test message',
            new Context(),
            Metadata::create()
        );

        $result = $this->formatter->format($message);

        $this->assertStringStartsWith('<?xml', $result);
        $this->assertStringContainsString('<log>', $result);
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

        $this->assertStringContainsString('<level>ERROR</level>', $result);
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

        $this->assertStringContainsString('<message>Test message</message>', $result);
    }

    #[Test]
    public function it_includes_datetime_in_output(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context()
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString('<datetime>2024-01-15 10:30:45</datetime>', $result);
    }

    #[Test]
    public function it_handles_nested_context(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context([
                'user' => [
                    'id' => 123,
                    'name' => 'John',
                ],
            ]),
            Metadata::create()
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString('<context>', $result);
        $this->assertStringContainsString('<user>', $result);
        $this->assertStringContainsString('<id>123</id>', $result);
        $this->assertStringContainsString('<name>John</name>', $result);
    }

    #[Test]
    public function it_escapes_special_characters(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Message with <tags> & "quotes"',
            new Context(),
            Metadata::create()
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString('&lt;tags&gt;', $result);
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('"quotes"', $result);
    }

    #[Test]
    public function it_produces_formatted_output(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context()
        );

        $result = $this->formatter->format($message);

        // Formatted XML should contain newlines and indentation
        $this->assertStringContainsString("\n", $result);
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
    public function it_handles_metadata(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context(),
            Metadata::create()
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString('<metadata>', $result);
        $this->assertStringContainsString('<timestamp>2024-01-15 10:30:45</timestamp>', $result);
    }

    #[Test]
    public function it_creates_valid_xml_document(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test message',
            new Context(['user' => 'john']),
            Metadata::create()
        );

        $result = $this->formatter->format($message);

        $xml = simplexml_load_string($result);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }

    #[Test]
    public function it_handles_empty_context(): void
    {
        $message = new Message(
            LogLevel::INFO,
            'Test',
            new Context([])
        );

        $result = $this->formatter->format($message);

        $this->assertStringContainsString('<context/>', $result);
    }
}
