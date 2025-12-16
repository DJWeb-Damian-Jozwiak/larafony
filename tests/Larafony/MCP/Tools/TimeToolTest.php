<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\MCP\Tools;

use Larafony\Framework\MCP\Tools\TimeTool;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Content\TextContent;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TimeToolTest extends TestCase
{
    private TimeTool $tool;

    protected function setUp(): void
    {
        $this->tool = new TimeTool();
    }

    public function testGetCurrentTimeHasMcpToolAttribute(): void
    {
        $reflection = new ReflectionClass(TimeTool::class);
        $method = $reflection->getMethod('getCurrentTime');
        $attributes = $method->getAttributes(McpTool::class);

        $this->assertCount(1, $attributes);

        $instance = $attributes[0]->newInstance();
        $this->assertSame('get_current_time', $instance->name);
    }

    public function testGetCurrentTimeReturnsTextContent(): void
    {
        $result = $this->tool->getCurrentTime();

        $this->assertInstanceOf(TextContent::class, $result);
    }

    public function testGetCurrentTimeUsesDefaultTimezone(): void
    {
        $result = $this->tool->getCurrentTime();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->assertStringContainsString(
            $now->format('Y-m-d'),
            $result->text
        );
    }

    public function testGetCurrentTimeUsesCustomTimezone(): void
    {
        $result = $this->tool->getCurrentTime('Europe/Warsaw');
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Warsaw'));

        $this->assertStringContainsString(
            $now->format('Y-m-d'),
            $result->text
        );
    }

    public function testGetCurrentTimeUsesCustomFormat(): void
    {
        $result = $this->tool->getCurrentTime('UTC', 'd/m/Y');
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->assertSame($now->format('d/m/Y'), $result->text);
    }

    public function testGetCurrentTimeHandlesInvalidTimezone(): void
    {
        $result = $this->tool->getCurrentTime('Invalid/Timezone');

        $this->assertStringContainsString('Error', $result->text);
        $this->assertStringContainsString('Invalid/Timezone', $result->text);
    }

    public function testParseDateHasMcpToolAttribute(): void
    {
        $reflection = new ReflectionClass(TimeTool::class);
        $method = $reflection->getMethod('parseDate');
        $attributes = $method->getAttributes(McpTool::class);

        $this->assertCount(1, $attributes);

        $instance = $attributes[0]->newInstance();
        $this->assertSame('parse_date', $instance->name);
    }

    public function testParseDateReturnsFormattedDate(): void
    {
        $result = $this->tool->parseDate('2024-06-15');

        $this->assertStringContainsString('2024-06-15', $result->text);
    }

    public function testParseDateUsesCustomFormat(): void
    {
        $result = $this->tool->parseDate('2024-06-15', 'd/m/Y');

        $this->assertSame('15/06/2024', $result->text);
    }

    public function testParseDateHandlesInvalidDate(): void
    {
        $result = $this->tool->parseDate('not-a-date');

        $this->assertStringContainsString('Error', $result->text);
    }
}
