<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache;

use DateInterval;

class TaggedCache
{
    /**
     * @param Cache $cache
     * @param array<int, string> $tags
     */
    public function __construct(
        private Cache $cache,
        private array $tags
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($this->taggedKey($key), $default);
    }

    public function put(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $taggedKey = $this->taggedKey($key);
        $this->addToTagReferences($taggedKey);

        return $this->cache->put($taggedKey, $value, $ttl);
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($this->taggedKey($key));
    }

    public function has(string $key): bool
    {
        return $this->cache->has($this->taggedKey($key));
    }

    public function remember(string $key, DateInterval|int $ttl, callable $callback): mixed
    {
        $taggedKey = $this->taggedKey($key);

        if ($this->cache->has($taggedKey)) {
            return $this->cache->get($taggedKey);
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Flush all cache items with these tags
     *
     * @return bool
     */
    public function flush(): bool
    {
        $success = true;

        foreach ($this->tags as $tag) {
            $refKey = $this->tagReferenceKey($tag);
            $keys = $this->cache->get($refKey, []);

            foreach ($keys as $key) {
                if (! $this->cache->forget($key)) {
                    $success = false;
                }
            }

            // Clear the tag reference itself
            $this->cache->forget($refKey);
        }

        return $success;
    }

    /**
     * Get all keys associated with a tag
     *
     * @param string $tag
     *
     * @return array<int, string>
     */
    public function getTagKeys(string $tag): array
    {
        return $this->cache->get($this->tagReferenceKey($tag), []);
    }

    /**
     * Get tagged cache key
     *
     * @param string $key
     *
     * @return string
     */
    private function taggedKey(string $key): string
    {
        $tagHash = md5(implode('|', $this->tags));
        return "tagged.{$tagHash}.{$key}";
    }

    /**
     * Get tag reference key
     *
     * @param string $tag
     *
     * @return string
     */
    private function tagReferenceKey(string $tag): string
    {
        return "tag.{$tag}.keys";
    }

    /**
     * Add key to tag references
     *
     * @param string $key
     *
     * @return void
     */
    private function addToTagReferences(string $key): void
    {
        foreach ($this->tags as $tag) {
            $refKey = $this->tagReferenceKey($tag);
            $keys = $this->cache->get($refKey, []);

            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
                $this->cache->forever($refKey, $keys);
            }
        }
    }
}
