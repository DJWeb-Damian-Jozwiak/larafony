<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP\Tools;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Content\TextContent;

/**
 * MCP tool for getting current date and time information.
 *
 * Demonstrates attribute-based tool registration with MCP SDK.
 * Tools marked with #[McpTool] are automatically discovered.
 */
class TimeTool
{
    #[McpTool(
        name: 'get_current_time',
        description: 'Get the current date and time in specified timezone',
    )]
    public function getCurrentTime(
        #[Schema(description: 'Timezone (e.g., UTC, Europe/Warsaw)', type: 'string')]
        string $timezone = 'UTC',
        #[Schema(description: 'Date format (PHP date format)', type: 'string')]
        string $format = 'Y-m-d H:i:s',
    ): TextContent {
        try {
            $tz = new \DateTimeZone($timezone);
            $now = new \DateTimeImmutable('now', $tz);

            return new TextContent($now->format($format));
        } catch (\Exception $e) {
            return new TextContent("Error: Invalid timezone '{$timezone}'");
        }
    }

    #[McpTool(
        name: 'parse_date',
        description: 'Parse a date string and return formatted output',
    )]
    public function parseDate(
        #[Schema(description: 'Date string to parse', type: 'string')]
        string $date,
        #[Schema(description: 'Output format', type: 'string')]
        string $format = 'Y-m-d H:i:s',
    ): TextContent {
        try {
            $parsed = new \DateTimeImmutable($date);

            return new TextContent($parsed->format($format));
        } catch (\Exception $e) {
            return new TextContent("Error: Cannot parse date '{$date}'");
        }
    }
}
