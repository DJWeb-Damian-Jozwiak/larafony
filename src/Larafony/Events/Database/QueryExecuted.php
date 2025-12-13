<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Database;

final readonly class QueryExecuted
{
    /**
     * @param array<int, StackFrameDto> $backtrace
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
