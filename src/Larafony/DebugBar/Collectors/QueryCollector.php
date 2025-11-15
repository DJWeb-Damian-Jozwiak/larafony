<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Collectors;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;
use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Database\QueryExecuted;

final class QueryCollector implements DataCollectorContract
{
    /**
     * @var array<int, array{sql: string, rawSql: string, time: float, connection: string, backtrace: array<int, array{file?: string, line?: int, class?: string, function?: string, compiled_file?: string, compiled_line?: int}>}>
     */
    private array $queries = [];

    private float $totalTime = 0.0;

    #[Listen]
    public function onQueryExecuted(QueryExecuted $event): void
    {
        $this->queries[] = [
            'sql' => $event->sql,
            'rawSql' => $event->rawSql,
            'time' => $event->time,
            'connection' => $event->connection,
            'backtrace' => $event->backtrace
                |> (static fn (array $backtrace) => array_filter(
                    $backtrace, static fn(array $line) => !str_contains($line['file'], 'Larafony')))
        ];

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
