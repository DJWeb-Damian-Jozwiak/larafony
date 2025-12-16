<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP\Session;

use Larafony\Framework\Cache\Cache;
use Mcp\Server\Session\SessionStoreInterface;
use Symfony\Component\Uid\Uuid;

/**
 * MCP session store backed by Larafony's cache.
 *
 * Stores MCP session data in Larafony's PSR-6 cache,
 * supporting all configured cache backends (file, Redis, Memcached).
 */
final class CacheSessionStore implements SessionStoreInterface
{
    private const string KEY_PREFIX = 'mcp_session_';

    /** @var array<string, int> */
    private array $sessionTimestamps = [];

    public function __construct(
        private readonly Cache $cache,
        private readonly int $ttl = 3600,
    ) {
    }

    public function exists(Uuid $id): bool
    {
        return $this->cache->has($this->key($id));
    }

    public function read(Uuid $id): string|false
    {
        $data = $this->cache->get($this->key($id));

        if ($data === null) {
            return false;
        }

        $this->touch($id);

        return $data;
    }

    public function write(Uuid $id, string $data): bool
    {
        $this->touch($id);

        return $this->cache->put($this->key($id), $data, $this->ttl);
    }

    public function destroy(Uuid $id): bool
    {
        unset($this->sessionTimestamps[$id->toRfc4122()]);

        return $this->cache->forget($this->key($id));
    }

    /**
     * @return array<Uuid>
     */
    public function gc(): array
    {
        $expired = [];
        $now = time();

        foreach ($this->sessionTimestamps as $uuidString => $timestamp) {
            if ($now - $timestamp > $this->ttl) {
                $uuid = Uuid::fromRfc4122($uuidString);
                $this->destroy($uuid);
                $expired[] = $uuid;
            }
        }

        return $expired;
    }

    private function key(Uuid $id): string
    {
        return self::KEY_PREFIX . $id->toRfc4122();
    }

    private function touch(Uuid $id): void
    {
        $this->sessionTimestamps[$id->toRfc4122()] = time();
    }
}
