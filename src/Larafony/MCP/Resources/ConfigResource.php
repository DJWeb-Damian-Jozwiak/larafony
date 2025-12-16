<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP\Resources;

use Larafony\Framework\Web\Config;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Schema\Content\TextContent;

/**
 * MCP resource exposing application configuration.
 *
 * Provides read-only access to non-sensitive configuration values,
 * helping AI assistants understand application setup.
 */
class ConfigResource
{
    #[McpResource(
        uri: 'config://app',
        name: 'Application Config',
        description: 'Application configuration (app.name, app.env, etc.)',
        mimeType: 'application/json',
    )]
    public function appConfig(): TextContent
    {
        return new TextContent(json_encode([
            'name' => Config::get('app.name'),
            'env' => Config::get('app.env'),
            'debug' => Config::get('app.debug'),
            'url' => Config::get('app.url'),
            'timezone' => Config::get('app.timezone'),
        ], JSON_PRETTY_PRINT));
    }

    #[McpResource(
        uri: 'config://database',
        name: 'Database Config',
        description: 'Database configuration (driver, host, database name)',
        mimeType: 'application/json',
    )]
    public function databaseConfig(): TextContent
    {
        return new TextContent(json_encode([
            'driver' => Config::get('database.driver'),
            'host' => Config::get('database.host'),
            'database' => Config::get('database.database'),
            'charset' => Config::get('database.charset'),
        ], JSON_PRETTY_PRINT));
    }

    #[McpResource(
        uri: 'config://cache',
        name: 'Cache Config',
        description: 'Cache configuration (driver, prefix)',
        mimeType: 'application/json',
    )]
    public function cacheConfig(): TextContent
    {
        return new TextContent(json_encode([
            'driver' => Config::get('cache.driver'),
            'prefix' => Config::get('cache.prefix'),
        ], JSON_PRETTY_PRINT));
    }
}
