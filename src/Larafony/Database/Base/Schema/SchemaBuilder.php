<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Schema;

use Closure;
use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Contracts\SchemaBuilderContract;
use Larafony\Framework\Database\Drivers\MySQL\Grammar;

abstract class SchemaBuilder implements SchemaBuilderContract
{
    protected Grammar $grammar;
    public function __construct(protected readonly ConnectionContract $connection)
    {
    }

    abstract public function create(string $table, Closure $callback): string;
    abstract public function table(string $table, Closure $callback): string;
    abstract public function drop(string $table): string;
    abstract public function dropIfExists(string $table): string;

    public function execute(string $sql): bool
    {
        $this->connection->query($sql);
        return true;
    }

    /**
     * @return array<int, string>
     */
    abstract public function getColumnListing(string $table): array;
}
