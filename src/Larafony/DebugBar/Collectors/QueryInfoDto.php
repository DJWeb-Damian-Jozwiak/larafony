<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Collectors;

use Larafony\Framework\Events\Database\StackFrameDto;

final readonly class QueryInfoDto
{
    /**
     * @param array<int, StackFrameDto> $backtrace
     */
    public function __construct(
        public string $sql,
        public string $rawSql,
        public float $time,
        public string $connection,
        public array $backtrace,
    ) {
    }
}
