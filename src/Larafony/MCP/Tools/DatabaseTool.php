<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP\Tools;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Database\Base\Contracts\DatabaseInfoContract;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Content\TextContent;

/**
 * MCP tool for database introspection.
 *
 * Provides safe, read-only access to database schema information.
 * Useful for AI assistants helping with database design and queries.
 */
class DatabaseTool
{
    public function __construct(
        private readonly ContainerContract $container,
    ) {
    }

    #[McpTool(
        name: 'list_tables',
        description: 'List all database tables with their basic information',
    )]
    public function listTables(): TextContent
    {
        try {
            $tableInfo = $this->container->get(DatabaseInfoContract::class);
            $tables = $tableInfo->getTables();

            if (empty($tables)) {
                return new TextContent('No tables found in database.');
            }

            $output = "Database tables:\n";
            foreach ($tables as $table) {
                $output .= "- {$table}\n";
            }

            return new TextContent($output);
        } catch (\Throwable $e) {
            return new TextContent("Error: {$e->getMessage()}");
        }
    }

    #[McpTool(
        name: 'describe_table',
        description: 'Get detailed schema information for a specific table',
    )]
    public function describeTable(
        #[Schema(description: 'Table name to describe', type: 'string')]
        string $table,
    ): TextContent {
        try {
            $tableInfo = $this->container->get(DatabaseInfoContract::class);
            $tableDefinition = $tableInfo->getTable($table);
            $columns = $tableDefinition->columns;

            if (empty($columns)) {
                return new TextContent("Table '{$table}' not found or has no columns.");
            }

            $output = "Table: {$table}\n\nColumns:\n";
            foreach ($columns as $name => $column) {
                $output .= "- {$name}\n";
            }

            return new TextContent($output);
        } catch (\Throwable $e) {
            return new TextContent("Error: {$e->getMessage()}");
        }
    }
}
