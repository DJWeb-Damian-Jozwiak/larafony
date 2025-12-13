<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Contracts;

interface StorageContract
{
    /**
     * Get cached data by key
     *
     * @param string $key
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array;

    /**
     * Set cached data
     *
     * @param array<string, mixed> $data
     */
    public function set(string $key, array $data): bool;

    /**
     * Delete cached data by key
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Clear all cached data
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Set maximum capacity
     *
     * @param int $size Maximum size (bytes for Redis, item count for File)
     *
     * @return void
     */
    public function maxCapacity(int $size): void;
}
