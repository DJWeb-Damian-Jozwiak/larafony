<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Collectors;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;
use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Database\QueryExecuted;
use Larafony\Framework\Events\Database\StackFrameDto;

final class QueryCollector implements DataCollectorContract
{
    /** @var array<int, QueryInfoDto> */
    private array $queries = [];

    private float $totalTime = 0.0;

    #[Listen]
    public function onQueryExecuted(QueryExecuted $event): void
    {
        $backtrace = array_values(array_filter(
            $event->backtrace,
            static fn (StackFrameDto $frame) => ! $frame->containsPath('Larafony'),
        ));

        $this->queries[] = new QueryInfoDto(
            sql: $event->sql,
            rawSql: $event->rawSql,
            time: $event->time,
            connection: $event->connection,
            backtrace: $backtrace,
        );

        $this->totalTime += $event->time;
    }

    public function collect(): array
    {
        return [
            'queries' => $this->queries,
            'count' => count($this->queries),
            'total_time' => round($this->totalTime, 2),
        ];
    }

    public function getName(): string
    {
        return 'queries';
    }
}
