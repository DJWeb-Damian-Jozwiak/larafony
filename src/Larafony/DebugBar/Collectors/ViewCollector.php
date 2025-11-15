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
            'data' => $this->serializeData($event->data),
            'data_keys' => array_keys($event->data),
            'renderTime' => $event->renderTime,
        ];

        $this->totalTime += $event->renderTime;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    private function serializeData(mixed $data): mixed
    {
        if (is_object($data)) {
            return get_class($data) . ' (object)';
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->serializeData($value);
            }
            return $result;
        }

        if (is_string($data) && strlen($data) > 100) {
            return substr($data, 0, 100) . '...';
        }

        return $data;
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
