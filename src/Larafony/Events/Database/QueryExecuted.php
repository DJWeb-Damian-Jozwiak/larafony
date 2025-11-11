<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Database;

final readonly class QueryExecuted
{
    /**
     * @param array<int, array{file?: string, line?: int, class?: string, function?: string, compiled_file?: string, compiled_line?: int}> $backtrace
     */
    public function __construct(
        public string $sql,
        public string $rawSql,
        public float $time,
        public string $connection = 'default',
        public array $backtrace = [],
    ) {
    }
}
