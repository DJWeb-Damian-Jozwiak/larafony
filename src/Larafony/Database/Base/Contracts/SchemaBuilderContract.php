<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Contracts;

use Closure;

interface SchemaBuilderContract
{
    /**
     * Generate SQL for creating a new table.
     */
    public function create(string $table, Closure $callback): string;

    /**
     * Generate SQL for modifying an existing table.
     */
    public function table(string $table, Closure $callback): string;

    /**
     * Generate SQL for dropping a table.
     */
    public function drop(string $table): string;

    /**
     * Generate SQL for dropping a table if it exists.
     */
    public function dropIfExists(string $table): string;

    /**
     * Execute raw SQL statement.
     */
    public function execute(string $sql): bool;

    /**
     * Get column listing for a table.
     *
     * @return array<int, string>
     */
    public function getColumnListing(string $table): array;
}
