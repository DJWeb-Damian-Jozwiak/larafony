<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache;

use Larafony\Framework\Cache\Contracts\StorageContract;
use Larafony\Framework\Clock\ClockFactory;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CacheItemPool implements CacheItemPoolInterface
{
    /**
     * @var array<string, CacheItemInterface>
     */
    private array $deferred = [];

    public function __construct(public readonly StorageContract $storage)
    {
    }

    public function getItem(string $key): CacheItemInterface
    {
        new CacheItemKeyValidator()->validate($key);
        $item = new CacheItem($key);
        $data = $this->storage->get($key);
        if ($data === null) {
            return $item;
        }

        $expiry = $data['expiry'] ?? null;
        $currentTime = ClockFactory::now()->getTimestamp();

        // Check if item is expired
        if ($expiry !== null && $expiry <= $currentTime) {
            // Item expired, delete it
            $this->storage->delete($key);
            return $item;
        }

        // Item is valid, populate it
        $item->set($data['value'])->withIsHit(true);

        if ($expiry !== null) {
            $item->expiresAt(new \DateTimeImmutable('@' . $expiry));
        }

        return $item;
    }

    /**
     * @param array<int, string> $keys
     *
     * @return iterable<string, CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            new CacheItemKeyValidator()->validate($key);
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    public function hasItem(string $key): bool
    {
        new CacheItemKeyValidator()->validate($key);
        return $this->getItem($key)->isHit();
    }

    public function clear(): bool
    {
        $this->deferred = [];
        return $this->storage->clear();
    }

    public function deleteItem(string $key): bool
    {
        new CacheItemKeyValidator()->validate($key);
        unset($this->deferred[$key]);
        return $this->storage->delete($key);
    }

    /**
     * @param array<int, string> $keys
     */
    public function deleteItems(array $keys): bool
    {
        array_walk($keys, static fn ($key) => new CacheItemKeyValidator()->validate($key));
        return array_all($keys, fn ($key) => $this->deleteItem($key));
    }

    public function save(CacheItemInterface $item): bool
    {
        $expiry = null;
        if ($item instanceof CacheItem) {
            $expiry = $item->expiry?->getTimestamp();
        }

        return $this->storage->set($item->getKey(), [
            'value' => $item->get(),
            'expiry' => $expiry,
        ]);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;
        return true;
    }

    public function commit(): bool
    {
        if (array_any($this->deferred, fn ($item) => ! $this->save($item))) {
            return false;
        }
        $this->deferred = [];
        return true;
    }
}
