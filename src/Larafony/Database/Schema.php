<?php

declare(strict_types=1);

namespace Larafony\Framework\Database;

use Closure;
use Larafony\Framework\Database\Base\Schema\SchemaBuilder;

class Schema
{
    protected static ?DatabaseManager $manager = null;

    public static function withManager(DatabaseManager $manager): static
    {
        self::$manager = $manager;
        return new static();
    }

    public static function getSchemaBuilder(?string $connection = null): SchemaBuilder
    {
        if (self::$manager === null) {
            throw new \RuntimeException('Database manager not set. Call Schema::setManager() first.');
        }

        return self::$manager->schema($connection);
    }

    public static function create(string $table, Closure $callback): void
    {
        self::getSchemaBuilder()->create($table, $callback);
    }

    public static function table(string $table, Closure $callback): void
    {
        self::getSchemaBuilder()->table($table, $callback);
    }

    public static function drop(string $table): void
    {
        self::getSchemaBuilder()->drop($table);
    }

    public static function dropIfExists(string $table): void
    {
        self::getSchemaBuilder()->dropIfExists($table);
    }

    public static function getColumnListing(string $table): array
    {
        return self::getSchemaBuilder()->getColumnListing($table);
    }
}