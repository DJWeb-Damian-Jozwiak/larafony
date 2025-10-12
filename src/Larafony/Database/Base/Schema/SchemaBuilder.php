<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema;

use Closure;
use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Drivers\MySQL\Grammar;

abstract class SchemaBuilder
{
    protected Grammar $grammar;
    public function __construct(protected readonly ConnectionContract $connection)
    {
    }

    abstract public function create(string $table, Closure $callback): void;
    abstract public function table(string $table, Closure $callback): void;
    abstract public function drop(string $table): void;
    abstract public function dropIfExists(string $table): void;

    /**
     * @return array<int, string>
     */
    abstract public function getColumnListing(string $table): array;
}
