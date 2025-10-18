<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Log;

use Larafony\Framework\Log\Context;
use Larafony\Framework\Log\PlaceholderProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlaceholderProcessor::class)]
final class PlaceholderProcessorTest extends TestCase
{
    private PlaceholderProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new PlaceholderProcessor();
    }

    #[Test]
    public function it_replaces_string_placeholders(): void
    {
        $message = 'User {username} logged in';
        $context = new Context(['username' => 'john_doe']);

        $result = $this->processor->process($message, $context);

        $this->assertSame('User john_doe logged in', $result);
    }

    #[Test]
    public function it_replaces_numeric_placeholders(): void
    {
        $message = 'User ID: {id}, Age: {age}';
        $context = new Context(['id' => 123, 'age' => 30]);

        $result = $this->processor->process($message, $context);

        $this->assertSame('User ID: 123, Age: 30', $result);
    }

    #[Test]
    public function it_replaces_boolean_placeholders(): void
    {
        $message = 'Active: {active}, Verified: {verified}';
        $context = new Context(['active' => true, 'verified' => false]);

        $result = $this->processor->process($message, $context);

        $this->assertSame('Active: true, Verified: false', $result);
    }

    #[Test]
    public function it_replaces_array_placeholders_with_json(): void
    {
        $message = 'Data: {data}';
        $context = new Context(['data' => ['key' => 'value', 'number' => 42]]);

        $result = $this->processor->process($message, $context);

        $this->assertSame('Data: {"key":"value","number":42}', $result);
    }

    #[Test]
    public function it_handles_stringable_objects(): void
    {
        $stringable = new class {
            public function __toString(): string
            {
                return 'stringable_value';
            }
        };

        $message = 'Object: {obj}';
        $context = new Context(['obj' => $stringable]);

        $result = $this->processor->process($message, $context);

        $this->assertSame('Object: stringable_value', $result);
    }

    #[Test]
    public function it_keeps_placeholders_that_dont_exist_in_context(): void
    {
        $message = 'User {username} has {points} points';
        $context = new Context(['username' => 'john']);

        $result = $this->processor->process($message, $context);

        $this->assertSame('User john has {points} points', $result);
    }

    #[Test]
    public function it_handles_unserializable_values(): void
    {
        $resource = fopen('php://memory', 'r');
        $message = 'Resource: {resource}';
        $context = new Context(['resource' => $resource]);

        $result = $this->processor->process($message, $context);

        $this->assertSame('Resource: [unserializable]', $result);
        fclose($resource);
    }

    #[Test]
    public function it_handles_multiple_placeholders(): void
    {
        $message = '{action} by {user} at {time} with result: {success}';
        $context = new Context([
            'action' => 'Login',
            'user' => 'admin',
            'time' => '10:30',
            'success' => true,
        ]);

        $result = $this->processor->process($message, $context);

        $this->assertSame('Login by admin at 10:30 with result: true', $result);
    }

    #[Test]
    public function it_handles_messages_without_placeholders(): void
    {
        $message = 'Simple log message without placeholders';
        $context = new Context(['some' => 'data']);

        $result = $this->processor->process($message, $context);

        $this->assertSame($message, $result);
    }

    #[Test]
    public function it_handles_unicode_in_arrays(): void
    {
        $message = 'User: {data}';
        $context = new Context(['data' => ['name' => 'Łódź', 'city' => 'Kraków']]);

        $result = $this->processor->process($message, $context);

        $this->assertStringContainsString('Łódź', $result);
        $this->assertStringContainsString('Kraków', $result);
    }
}
