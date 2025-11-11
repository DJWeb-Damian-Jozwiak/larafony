<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Collectors;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;

final class PerformanceCollector implements DataCollectorContract
{
    private float $startTime;
    private int $startMemory;

    public function __construct()
    {
        $this->startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $this->startMemory = memory_get_usage();
    }

    public function collect(): array
    {
        $currentMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();

        return [
            'execution_time' => round((microtime(true) - $this->startTime) * 1000, 2),
            'memory_usage' => $this->formatBytes($currentMemory),
            'memory_usage_bytes' => $currentMemory,
            'peak_memory' => $this->formatBytes($peakMemory),
            'peak_memory_bytes' => $peakMemory,
            'memory_delta' => $this->formatBytes($currentMemory - $this->startMemory),
            'memory_delta_bytes' => $currentMemory - $this->startMemory,
        ];
    }

    public function getName(): string
    {
        return 'performance';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = $bytes;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, 2) . ' ' . $units[$i];
    }
}
