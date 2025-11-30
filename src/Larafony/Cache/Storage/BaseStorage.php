<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Storage;

use Larafony\Framework\Cache\Contracts\StorageContract;

abstract class BaseStorage implements StorageContract
{
    /**
     * In-memory cache to avoid repeated backend calls within same request
     *
     * @var array<string, array<string, mixed>|null>
     */
    protected array $inMemoryCache = [];

    /**
     * Maximum number of items to keep in memory cache
     * Prevents memory leaks in long-running processes
     */
    protected int $maxInMemoryCacheSize = 1000;

    /**
     * Enable compression for large values
     */
    protected bool $compressionEnabled = true;

    /**
     * Minimum size (in bytes) before compression is applied
     * Default: 10KB
     */
    protected int $compressionThreshold = 10240;

    /**
     * Get cached data by key (with in-memory cache)
     *
     * @param string $key
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        // Check in-memory cache first
        if (array_key_exists($key, $this->inMemoryCache)) {
            return $this->inMemoryCache[$key];
        }

        // Fetch from backend storage
        $data = $this->getFromBackend($key);

        // Store in memory for this request with LRU eviction
        $this->addToInMemoryCache($key, $data);

        return $data;
    }

    /**
     * Set cached data (invalidates in-memory cache for this key)
     *
     * @param string $key
     * @param array<string, mixed> $data
     *
     * @return bool
     */
    public function set(string $key, array $data): bool
    {
        $result = $this->addToBackend($key, $data);

        if ($result) {
            // Update in-memory cache
            $this->inMemoryCache[$key] = $data;
        }

        return $result;
    }

    /**
     * Delete cached data (invalidates in-memory cache)
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        // Remove from in-memory cache
        unset($this->inMemoryCache[$key]);

        return $this->deleteFromBackend($key);
    }

    /**
     * Clear all cached data (including in-memory)
     *
     * @return bool
     */
    public function clear(): bool
    {
        // Clear in-memory cache
        $this->inMemoryCache = [];

        return $this->clearBackend();
    }

    /**
     * Enable or disable compression (fluent interface)
     *
     * @param bool $enabled
     *
     * @return static
     */
    public function withCompression(bool $enabled): static
    {
        $this->compressionEnabled = $enabled;
        return $this;
    }

    /**
     * Set compression threshold in bytes (fluent interface)
     *
     * @param int $bytes
     *
     * @return static
     */
    public function withCompressionThreshold(int $bytes): static
    {
        $this->compressionThreshold = $bytes;
        return $this;
    }

    /**
     * Get data from backend storage
     *
     * @param string $key
     *
     * @return array<string, mixed>|null
     */
    abstract protected function getFromBackend(string $key): ?array;

    /**
     * Set data to backend storage
     *
     * @param string $key
     * @param array<string, mixed> $data
     *
     * @return bool
     */
    abstract protected function addToBackend(string $key, array $data): bool;

    /**
     * Delete data from backend storage
     *
     * @param string $key
     *
     * @return bool
     */
    abstract protected function deleteFromBackend(string $key): bool;

    /**
     * Clear all data from backend storage
     *
     * @return bool
     */
    abstract protected function clearBackend(): bool;

    /**
     * Compress data if it exceeds threshold
     *
     * @param string $data
     *
     * @return string
     */
    protected function maybeCompress(string $data): string
    {
        if (! $this->compressionEnabled) {
            return $data;
        }

        if (strlen($data) < $this->compressionThreshold) {
            return $data;
        }

        // Prefix with 'C:' to indicate compression
        // Compression level 6 is a good balance between speed and ratio
        $compressed = gzcompress($data, 6);

        if ($compressed === false) {
            return $data;
        }

        return 'C:' . $compressed;
    }

    /**
     * Decompress data if it was compressed
     *
     * @param string $data
     *
     * @return string
     */
    protected function maybeDecompress(string $data): string
    {
        if (! str_starts_with($data, 'C:')) {
            return $data;
        }

        $decompressed = gzuncompress(substr($data, 2));

        if ($decompressed === false) {
            // Decompression failed, return original
            return $data;
        }

        return $decompressed;
    }

    /**
     * Add item to in-memory cache with automatic eviction
     *
     * @param string $key
     * @param array<string, mixed>|null $data
     *
     * @return void
     */
    private function addToInMemoryCache(string $key, ?array $data): void
    {
        // If cache is full, remove oldest item (LRU)
        if (count($this->inMemoryCache) >= $this->maxInMemoryCacheSize) {
            array_shift($this->inMemoryCache);
        }

        $this->inMemoryCache[$key] = $data;
    }
}
