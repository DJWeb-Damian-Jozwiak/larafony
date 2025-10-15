<?php

declare(strict_types=1);

namespace Larafony\Framework\Database;

use Closure;
use Larafony\Framework\Database\Base\Schema\SchemaBuilder;

class Schema
{
    protected static ?DatabaseManager $manager = null;

    public static function withManager(DatabaseManager $manager): self
    {
        self::$manager = $manager;
        return new self();
    }

    public static function getSchemaBuilder(?string $connection = null): SchemaBuilder
    {
        if (self::$manager === null) {
            throw new \RuntimeException('Database manager not set. Call Schema::setManager() first.');
        }

        return self::$manager->schema($connection);
    }

    #[\NoDiscard]
    public static function create(string $table, Closure $callback): string
    {
        return self::getSchemaBuilder()->create($table, $callback);
    }

    #[\NoDiscard]
    public static function table(string $table, Closure $callback): string
    {
        return self::getSchemaBuilder()->table($table, $callback);
    }

    #[\NoDiscard]
    public static function drop(string $table): string
    {
        return self::getSchemaBuilder()->drop($table);
    }

    #[\NoDiscard]
    public static function dropIfExists(string $table): string
    {
        return self::getSchemaBuilder()->dropIfExists($table);
    }

    public static function execute(string $sql): bool
    {
        return self::getSchemaBuilder()->execute($sql);
    }

    /**
     * @return array<int, string>
     */
    public static function getColumnListing(string $table): array
    {
        return self::getSchemaBuilder()->getColumnListing($table);
    }
}
