<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache;

use DateTimeImmutable;
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

    /**
     * Validate cache key according to PSR-6 specification
     *
     * @param string $key
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function validateKey(string $key): void
    {
        // PSR-6: Key MUST NOT contain: {}()/\@:
        if (preg_match('/[{}()\\/\\\\@:]/', $key)) {
            throw new \InvalidArgumentException(
                "Cache key \"$key\" contains invalid characters. " .
                "Reserved characters are: {}()/\\@:"
            );
        }

        // Reasonable length limit
        if (strlen($key) > 64) {
            throw new \InvalidArgumentException(
                "Cache key \"$key\" is too long (max 64 characters)"
            );
        }

        // Must not be empty
        if ($key === '') {
            throw new \InvalidArgumentException(
                "Cache key cannot be empty"
            );
        }
    }

    public function getItem(string $key): CacheItemInterface
    {
        $this->validateKey($key);
        if (isset($this->deferred[$key])) {
            return $this->deferred[$key];
        }
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

        // Only set expiry if it exists (lazy creation of DateTimeImmutable)
        if ($expiry !== null) {
            $item->expiresAt(new DateTimeImmutable('@' . $expiry));
        }

        return $item;
    }

    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $this->validateKey($key);
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    public function hasItem(string $key): bool
    {
        $this->validateKey($key);
        return $this->getItem($key)->isHit();
    }

    public function clear(): bool
    {
        $this->deferred = [];
        return $this->storage->clear();
    }

    public function deleteItem(string $key): bool
    {
        $this->validateKey($key);
        unset($this->deferred[$key]);
        return $this->storage->delete($key);
    }

    public function deleteItems(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $this->validateKey($key);
            if (!$this->deleteItem($key)) {
                $success = false;
            }
        }
        return $success;
    }

    public function save(CacheItemInterface $item): bool
    {
        $expiry = null;
        if (property_exists($item, 'expiry')) {
            $expiryObj = $item->expiry;
            $expiry = $expiryObj?->getTimestamp();
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
        if (array_any($this->deferred, fn($item) => !$this->save($item))) {
            return false;
        }
        $this->deferred = [];
        return true;
    }
}