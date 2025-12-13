<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;

final class DebugBar
{
    /**
     * @var array<string, DataCollectorContract>
     */
    private array $collectors = [];

    private bool $enabled = true;

    public function addCollector(string $name, DataCollectorContract $collector): void
    {
        $this->collectors[$name] = $collector;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<string, mixed>
     */
    public function collect(): array
    {
        if (! $this->enabled) {
            return [];
        }

        $data = array_map(static function ($collector) {
            return $collector->collect();
        }, $this->collectors);

        return array_filter($data);
    }

    /**
     * @return array<string, DataCollectorContract>
     */
    public function getCollectors(): array
    {
        return $this->collectors;
    }
}
