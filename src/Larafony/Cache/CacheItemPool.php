<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache;

use Larafony\Framework\Cache\Contracts\StorageContract;
use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Events\Cache\CacheHit;
use Larafony\Framework\Events\Cache\CacheMissed;
use Larafony\Framework\Events\Cache\KeyWritten;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class CacheItemPool implements CacheItemPoolInterface
{
    /**
     * @var array<string, CacheItemInterface>
     */
    private array $deferred = [];

    public function __construct(
        public readonly StorageContract $storage,
        private readonly ?EventDispatcherInterface $dispatcher = null
    ) {
    }

    public function getItem(string $key): CacheItemInterface
    {
        new CacheItemKeyValidator()->validate($key);
        $item = new CacheItem($key);
        $data = $this->storage->get($key);

        if ($data === null) {
            $this->dispatcher?->dispatch(new CacheMissed($key));
            return $item;
        }

        $expiry = $data['expiry'] ?? null;
        $currentTime = ClockFactory::now()->getTimestamp();

        // Check if item is expired
        if ($expiry !== null && $expiry <= $currentTime) {
            // Item expired, delete it
            $this->storage->delete($key);
            $this->dispatcher?->dispatch(new CacheMissed($key));
            return $item;
        }

        // Item is valid, populate it
        $item->set($data['value'])->withIsHit(true);

        // Get expiry as DateTimeImmutable for the event
        $expiryTime = $expiry !== null ? new \DateTimeImmutable('@' . $expiry) : null;

        $this->dispatcher?->dispatch(new CacheHit($key, $data['value'], $expiryTime));

        // Only set expiry if it exists (lazy creation of DateTimeImmutable)
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

        $data = [
            'value' => $item->get(),
            'expiry' => $expiry,
        ];

        $result = $this->storage->set($item->getKey(), $data);

        if ($result) {
            $ttl = $expiry ? $expiry - ClockFactory::now()->getTimestamp() : null;

            // Calculate size (serialized)
            $size = $data |> serialize(...) |> strlen(...);

            $this->dispatcher?->dispatch(new KeyWritten($item->getKey(), $item->get(), $ttl, $size));
        }

        return $result;
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
