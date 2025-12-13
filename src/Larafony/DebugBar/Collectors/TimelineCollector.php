<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Collectors;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;
use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Database\QueryExecuted;
use Larafony\Framework\Events\Framework\ApplicationBooted;
use Larafony\Framework\Events\Framework\ApplicationBooting;
use Larafony\Framework\Events\Routing\RouteMatched;
use Larafony\Framework\Events\View\ViewRendered;
use Larafony\Framework\Events\View\ViewRendering;

final class TimelineCollector implements DataCollectorContract
{
    /**
     * @var array<int, array{label: string, start: float, end: float, duration: float, memory: int, type: string}>
     */
    private array $events = [];

    private float $startTime;
    private float $lastEventTime;

    /**
     * @var array<string, float>
     */
    private array $pendingMeasures = [];

    public function __construct()
    {
        if (defined('APPLICATION_START')) {
            $this->startTime = APPLICATION_START;
        } else {
            $this->startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        }
        $this->lastEventTime = $this->startTime;
    }

    #[Listen]
    public function onApplicationBooting(ApplicationBooting $event): void
    {
        $now = microtime(true);
        $this->addEvent('Application Bootstrap', $this->lastEventTime, $now, 'framework');
        $this->lastEventTime = $now;
    }

    #[Listen]
    public function onApplicationBooted(ApplicationBooted $event): void
    {
        $now = microtime(true);
        $this->addEvent('Service Providers Boot', $this->lastEventTime, $now, 'framework');
        $this->lastEventTime = $now;
    }

    #[Listen]
    public function onRouteMatched(RouteMatched $event): void
    {
        $now = microtime(true);
        $name = "Route Matched: {$event->route->path} ({$event->route->method->value})";
        $this->addEvent($name, $this->lastEventTime, $now, 'routing');
        $this->lastEventTime = $now;
    }

    #[Listen]
    public function onQueryExecuted(QueryExecuted $event): void
    {
        $now = microtime(true);
        $start = $now - ($event->time / 1000);
        $sql = strlen($event->rawSql) > 50 ? substr($event->rawSql, 0, 50) . '...' : $event->rawSql;
        $this->addEvent("Query: {$sql}", $start, $now, 'database');
    }

    #[Listen]
    public function onViewRendering(ViewRendering $event): void
    {
        $this->pendingMeasures['view_' . $event->view] = microtime(true);
    }

    #[Listen]
    public function onViewRendered(ViewRendered $event): void
    {
        $now = microtime(true);
        $start = $this->pendingMeasures['view_' . $event->view] ?? $now - ($event->renderTime / 1000);
        unset($this->pendingMeasures['view_' . $event->view]);
        $this->addEvent("View Rendered: {$event->view}", $start, $now, 'view');
    }

    public function collect(): array
    {
        // Sort events by start time
        usort($this->events, static fn ($a, $b) => $a['start'] <=> $b['start']);

        return [
            'events' => $this->events,
            'total_time' => round((microtime(true) - $this->startTime) * 1000, 2),
            'start_time' => $this->startTime,
        ];
    }

    public function getName(): string
    {
        return 'timeline';
    }

    private function addEvent(string $label, float $start, float $end, string $type): void
    {
        $this->events[] = [
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'duration' => round(($end - $start) * 1000, 2),
            'memory' => memory_get_usage(),
            'type' => $type,
        ];
    }
}
