<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP\Contracts;

use Mcp\Server;

/**
 * Contract for MCP server factory.
 *
 * Creates configured MCP server instances integrated with Larafony's
 * container, event dispatcher, and cache.
 */
interface McpServerFactoryContract
{
    /**
     * Create a new MCP server with Larafony integration.
     *
     * @param string $name Server name for identification
     * @param string $version Server version
     * @param string|null $instructions Optional instructions for AI model
     * @param string|null $discoveryPath Path for automatic tool/resource discovery
     * @param string[] $discoveryDirs Directories to scan relative to discoveryPath
     */
    public function create(
        string $name,
        string $version,
        ?string $instructions = null,
        ?string $discoveryPath = null,
        array $discoveryDirs = ['src', '.'],
    ): Server;
}
