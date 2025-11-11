<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Collectors;

use Larafony\Framework\DebugBar\Contracts\DataCollectorContract;
use Larafony\Framework\Events\Attributes\Listen;
use Larafony\Framework\Events\Cache\CacheHit;
use Larafony\Framework\Events\Cache\CacheMissed;
use Larafony\Framework\Events\Cache\KeyForgotten;
use Larafony\Framework\Events\Cache\KeyWritten;

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

    #[Listen]
    public function onCacheHit(CacheHit $event): void
    {
        $this->operations[] = [
            'type' => 'hit',
            'key' => $event->key,
            'time' => microtime(true),
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
        ];
        $this->writes++;
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
            'hit_ratio' => $this->hits + $this->misses > 0
                ? round(($this->hits / ($this->hits + $this->misses)) * 100, 2)
                : 0,
        ];
    }

    public function getName(): string
    {
        return 'cache';
    }
}
