<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache;

use DateInterval;

/**
 * Cache warming utility for preloading frequently accessed data
 */
class CacheWarmer
{
    /**
     * @var array<int, array{key: string, callback: callable, ttl: DateInterval|int|null, tags: array<int, string>}>
     */
    private array $warmers = [];

    public function __construct(
        private Cache $cache
    ) {
    }

    /**
     * Register a cache warmer
     *
     * @param string $key Cache key to warm
     * @param callable $callback Function that generates the value
     * @param DateInterval|int|null $ttl Time to live
     * @param array<int, string> $tags Optional tags for group invalidation
     * @return self
     */
    public function register(
        string $key,
        callable $callback,
        DateInterval|int|null $ttl = null,
        array $tags = []
    ): self {
        $this->warmers[] = [
            'key' => $key,
            'callback' => $callback,
            'ttl' => $ttl,
            'tags' => $tags,
        ];

        return $this;
    }

    /**
     * Warm a single cache key
     *
     * @param string $key
     * @param callable $callback
     * @param DateInterval|int|null $ttl
     * @param array<int, string> $tags
     * @return bool
     */
    public function warm(
        string $key,
        callable $callback,
        DateInterval|int|null $ttl = null,
        array $tags = []
    ): bool {
        try {
            $value = $callback();

            if (empty($tags)) {
                return $this->cache->put($key, $value, $ttl);
            }

            return $this->cache->tags($tags)->put($key, $value, $ttl);
        } catch (\Throwable $e) {
            error_log("[Cache Warmer] Failed to warm cache key '{$key}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Warm all registered cache entries
     *
     * @param bool $force Force warming even if keys already exist
     * @return array{total: int, warmed: int, skipped: int, failed: int}
     */
    public function warmAll(bool $force = false): array
    {
        $total = count($this->warmers);
        $warmed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->warmers as $warmer) {
            $key = $warmer['key'];
            $callback = $warmer['callback'];
            $ttl = $warmer['ttl'];
            $tags = $warmer['tags'];

            // Skip if already cached (unless forced)
            if (!$force && $this->cache->has($key)) {
                $skipped++;
                continue;
            }

            $success = $this->warm($key, $callback, $ttl, $tags);

            if ($success) {
                $warmed++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => $total,
            'warmed' => $warmed,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    /**
     * Clear all registered warmers
     *
     * @return self
     */
    public function clear(): self
    {
        $this->warmers = [];
        return $this;
    }

    /**
     * Get count of registered warmers
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->warmers);
    }

    /**
     * Warm cache entries in batches for better performance
     *
     * @param int $batchSize
     * @param bool $force
     * @return array{total: int, warmed: int, skipped: int, failed: int, batches: int}
     */
    public function warmInBatches(int $batchSize = 10, bool $force = false): array
    {
        $batches = array_chunk($this->warmers, $batchSize);
        $result = [
            'total' => count($this->warmers),
            'warmed' => 0,
            'skipped' => 0,
            'failed' => 0,
            'batches' => count($batches),
        ];

        foreach ($batches as $batch) {
            foreach ($batch as $warmer) {
                $key = $warmer['key'];
                $callback = $warmer['callback'];
                $ttl = $warmer['ttl'];
                $tags = $warmer['tags'];

                if (!$force && $this->cache->has($key)) {
                    $result['skipped']++;
                    continue;
                }

                $success = $this->warm($key, $callback, $ttl, $tags);

                if ($success) {
                    $result['warmed']++;
                } else {
                    $result['failed']++;
                }
            }

            // Allow other processes to run between batches
            usleep(100);
        }

        return $result;
    }
}
