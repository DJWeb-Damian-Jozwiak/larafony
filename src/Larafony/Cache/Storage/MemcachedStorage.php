<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Storage;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Log\Log;
use Memcached;

class MemcachedStorage extends BaseStorage
{
    public function __construct(
        private Memcached $memcached,
        private string $prefix = 'cache:',
    ) {
    }

    /**
     * Set maximum memory capacity
     * Note: Memcached memory limits are typically set in server configuration
     *
     * @param int $size Size in bytes (advisory - actual limit set in memcached.ini)
     *
     * @return void
     */
    public function maxCapacity(int $size): void
    {
        // Memcached memory limits are set at server level
        // This is here for interface compliance
        // Log a warning that this should be configured at server level
        Log::error('Memcached memory limits should be configured in memcached server settings');
    }

    /**
     * Get cached data from Memcached backend
     *
     * @param string $key
     *
     * @return array<string, mixed>|null
     */
    protected function getFromBackend(string $key): ?array
    {
        $data = $this->memcached->get($this->prefix . $key);

        // Memcached returns false on miss or error
        if ($data === false) {
            // Check if it's an actual error or just a miss
            $resultCode = $this->memcached->getResultCode();
            if ($resultCode !== Memcached::RES_SUCCESS && $resultCode !== Memcached::RES_NOTFOUND) {
                // Only log real errors, not cache misses
                Log::error('Memcached get error: ' . $this->memcached->getResultMessage());
            }
            return null;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * Set cached data to Memcached backend
     *
     * @param string $key
     * @param array<string, mixed> $data
     *
     * @return bool
     */
    protected function addToBackend(string $key, array $data): bool
    {
        $key = $this->prefix . $key;

        // Calculate TTL from expiry timestamp
        $ttl = isset($data['expiry']) ? $data['expiry'] - ClockFactory::now()->getTimestamp() : 0;

        // If TTL is negative, item has already expired
        if ($ttl < 0) {
            return false;
        }

        // Memcached expects TTL as expiration time
        // 0 means no expiration
        $expiration = $ttl > 0 ? ClockFactory::now()->getTimestamp() + $ttl : 0;

        $result = $this->memcached->set($key, $data, $expiration);

        if (! $result) {
            Log::error('Memcached set error: ' . $this->memcached->getResultMessage());
        }

        return $result;
    }

    /**
     * Delete cached data from Memcached backend
     *
     * @param string $key
     *
     * @return bool
     */
    protected function deleteFromBackend(string $key): bool
    {
        $result = $this->memcached->delete($this->prefix . $key);

        // Memcached returns false if key doesn't exist, but we consider that success
        if (! $result && $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND) {
            Log::error('Memcached delete error: ' . $this->memcached->getResultMessage());
            return false;
        }

        return true;
    }

    /**
     * Clear all cached data from Memcached backend
     * Note: Memcached doesn't support prefix-based clearing natively,
     * so this flushes ALL data from the Memcached instance.
     *
     * @return bool
     */
    protected function clearBackend(): bool
    {
        // Memcached's getAllKeys() is unreliable across different versions
        // and may return stale data. Using flush() is the only reliable way.
        return $this->memcached->flush();
    }
}
