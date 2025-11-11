<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Collectors;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;
use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\View\ViewRendered;

final class ViewCollector implements DataCollectorContract
{
    /**
     * @var array<int, array{view: string, data: array<string, mixed>, renderTime: float}>
     */
    private array $views = [];

    private float $totalTime = 0.0;

    #[Listen]
    public function onViewRendered(ViewRendered $event): void
    {
        $this->views[] = [
            'view' => $event->view,
            'data' => array_keys($event->data),
            'renderTime' => $event->renderTime,
        ];

        $this->totalTime += $event->renderTime;
    }

    public function collect(): array
    {
        return [
            'views' => $this->views,
            'count' => count($this->views),
            'total_time' => round($this->totalTime, 2),
        ];
    }

    public function getName(): string
    {
        return 'views';
    }
}
