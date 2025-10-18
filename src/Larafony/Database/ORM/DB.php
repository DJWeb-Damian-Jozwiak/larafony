<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;

class DB
{
    protected static ?DatabaseManager $manager = null;

    public static function withManager(DatabaseManager $manager): self
    {
        self::$manager = $manager;
        return new self();
    }

    public static function table(string $table, ?string $connection = null): QueryBuilder
    {
        if (self::$manager === null) {
            throw new \RuntimeException('Database manager not set. Call DB::withManager() first.');
        }

        return self::$manager->table($table, $connection);
    }
}
