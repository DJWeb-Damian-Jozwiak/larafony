<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Storage;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Log\Log;
use Memcached;
use Throwable;

class MemcachedStorage extends BaseStorage
{
    /**
     * @param Memcached $memcached
     * @param string $prefix
     */
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
     * @return array|null
     */
    protected function getFromBackend(string $key): ?array
    {
        try {
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
        } catch (Throwable $e) {
            Log::error(' Memcached get exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Set cached data to Memcached backend
     *
     * @param string $key
     * @param array $data
     * @return bool
     */
    protected function setToBackend(string $key, array $data): bool
    {
        try {
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

            if (!$result) {
                Log::error('Memcached set error: ' . $this->memcached->getResultMessage());
            }

            return $result;
        } catch (Throwable $e) {
            Log::error('Memcached set exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete cached data from Memcached backend
     *
     * @param string $key
     * @return bool
     */
    protected function deleteFromBackend(string $key): bool
    {
        $result = $this->memcached->delete($this->prefix . $key);

        // Memcached returns false if key doesn't exist, but we consider that success
        if (!$result && $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND) {
            Log::error('Memcached delete error: ' . $this->memcached->getResultMessage());
            return false;
        }

        return true;
    }

    /**
     * Clear all cached data with prefix from Memcached backend
     * Note: Memcached doesn't support prefix-based clearing natively
     * This will attempt to track keys or use flush (which clears ALL data)
     *
     * @return bool
     */
    protected function clearBackend(): bool
    {
        // Get all keys - note this requires getAllKeys() which may not work on all Memcached versions
        $allKeys = $this->memcached->getAllKeys();

        // getAllKeys() often returns false or empty array even when keys exist
        // This is a known Memcached limitation - flush is the reliable way to clear
        if ($allKeys === false || empty($allKeys)) {
            // Fallback to flush (clears ALL data from this Memcached instance)
            // For testing with prefix, we accept this limitation
            return $this->memcached->flush();
        }

        // Filter keys by prefix and delete them (rarely works in practice)
        $deleted = 0;
        foreach ($allKeys as $key) {
            if (str_starts_with($key, $this->prefix)) {
                // Key already includes prefix, don't add it again
                if ($this->memcached->delete($key)) {
                    $deleted++;
                }
            }
        }

        return true;
    }
}