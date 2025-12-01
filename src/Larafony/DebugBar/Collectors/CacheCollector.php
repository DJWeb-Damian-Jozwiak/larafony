<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Collectors;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;
use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Cache\CacheHit;
use Larafony\Framework\Events\Cache\CacheMissed;
use Larafony\Framework\Events\Cache\KeyForgotten;
use Larafony\Framework\Events\Cache\KeyWritten;
use Larafony\Framework\Web\Config;

final class CacheCollector implements DataCollectorContract
{
    /**
     * @var array<int, array{type: string, key: string, time: float}>
     */
    private array $operations = [];

    private int $hits = 0;
    private int $misses = 0;
    private int $writes = 0;
    private int $deletes = 0;
    private int $totalSize = 0;  // Total bytes written

    #[Listen]
    public function onCacheHit(CacheHit $event): void
    {
        $format = Config::get('cache.debugbar_format');
        $expiresAt = $event->expiresAt?->format($format->value ?? 'Y-m-d H:i:s');

        $this->operations[] = [
            'type' => 'hit',
            'key' => $event->key,
            'time' => microtime(true),
            'expires_at' => $expiresAt,
        ];
        $this->hits++;
    }

    #[Listen]
    public function onCacheMissed(CacheMissed $event): void
    {
        $this->operations[] = [
            'type' => 'miss',
            'key' => $event->key,
            'time' => microtime(true),
        ];
        $this->misses++;
    }

    #[Listen]
    public function onKeyWritten(KeyWritten $event): void
    {
        $this->operations[] = [
            'type' => 'write',
            'key' => $event->key,
            'time' => microtime(true),
            'ttl' => $event->ttl,
            'size' => $event->size,
        ];
        $this->writes++;
        $this->totalSize += $event->size ?? 0;
    }

    #[Listen]
    public function onKeyForgotten(KeyForgotten $event): void
    {
        $this->operations[] = [
            'type' => 'delete',
            'key' => $event->key,
            'time' => microtime(true),
        ];
        $this->deletes++;
    }

    public function collect(): array
    {
        return [
            'operations' => $this->operations,
            'hits' => $this->hits,
            'misses' => $this->misses,
            'writes' => $this->writes,
            'deletes' => $this->deletes,
            'total' => count($this->operations),
            'total_size' => $this->totalSize,
            'hit_ratio' => $this->hits + $this->misses > 0
                ? round($this->hits / ($this->hits + $this->misses) * 100, 2)
                : 0,
        ];
    }

    public function getName(): string
    {
        return 'cache';
    }
}
