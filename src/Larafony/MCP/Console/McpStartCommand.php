<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP\Console;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\MCP\Contracts\McpServerFactoryContract;
use Mcp\Server\Transport\StdioTransport;

/**
 * Console command to start an MCP server via STDIO transport.
 *
 * Configuration is read from config/mcp.php:
 * - name: Server name
 * - version: Server version
 * - instructions: Instructions for AI model
 * - discovery.path: Path for automatic tool/resource discovery
 * - discovery.dirs: Directories to scan (default: ['src'])
 */
#[AsCommand(name: 'mcp:start')]
class McpStartCommand extends Command
{
    public function run(): int
    {
        $config = $this->container->get(ConfigContract::class);

        $name = $config->get('mcp.name', $config->get('app.name', 'Larafony MCP Server'));
        $version = $config->get('mcp.version', '1.0.0');
        $instructions = $config->get('mcp.instructions');
        $discoveryPath = $config->get('mcp.discovery.path');

        $this->output->info("Starting MCP server: {$name} v{$version}");

        if ($discoveryPath !== null) {
            $this->output->info("Tool discovery path: {$discoveryPath}");
        }

        $factory = $this->container->get(McpServerFactoryContract::class);

        $server = $factory->create(
            name: $name,
            version: $version,
            instructions: $instructions,
            discoveryPath: $discoveryPath,
        );

        $transport = new StdioTransport();
        $server->run($transport);

        return 0;
    }
}
