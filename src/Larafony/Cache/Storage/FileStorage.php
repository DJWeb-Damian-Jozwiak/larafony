<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache\Storage;

use Larafony\Framework\Cache\Contracts\StorageContract;
use Larafony\Framework\Storage\Directory;

class FileStorage extends BaseStorage
{
    private array $accessLog;
    private int $maxItems;
    private string $metaFile = 'meta.json';

    /**
     * @param string $directory Cache directory path
     */
    public function __construct(
        private string $directory,
    ) {
        Directory::ensureDirectoryExists($this->directory);
        $this->accessLog = [];
        $this->maxItems = 1000;
        $this->loadMeta();
    }

    /**
     * Delete cached data by key
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        unset($this->accessLog[$key]);
        $this->saveMeta();

        // Call parent to clear in-memory cache and delegate to deleteFromBackend
        return parent::delete($key);
    }

    /**
     * Set maximum capacity (number of items)
     *
     * @param int $size
     * @return void
     */
    public function maxCapacity(int $size): void
    {
        $this->maxItems = $size;
        while ($this->getCurrentItemCount() > $size) {
            $this->evictLRU();
        }
    }

    /**
     * Get file path for key
     *
     * @param string $key
     * @return string
     */
    private function getPath(string $key): string
    {
        return $this->directory . '/' . md5($key) . '.cache';
    }

    /**
     * Load access log metadata
     *
     * @return void
     */
    private function loadMeta(): void
    {
        $metaPath = $this->directory . '/' . $this->metaFile;
        if (file_exists($metaPath)) {
            $contents = file_get_contents($metaPath);
            if ($contents !== false) {
                $decoded = json_decode($contents, true);
                if (is_array($decoded)) {
                    $this->accessLog = $decoded;
                }
            }
        }
    }

    /**
     * Save access log metadata
     *
     * @return void
     */
    private function saveMeta(): void
    {
        file_put_contents(
            $this->directory . '/' . $this->metaFile,
            json_encode($this->accessLog),
        );
    }

    /**
     * Evict least recently used item
     *
     * @return void
     */
    private function evictLRU(): void
    {
        $this->loadMeta();
        if (empty($this->accessLog)) {
            return;
        }

        asort($this->accessLog);
        $key = array_key_first($this->accessLog);
        if ($key !== null) {
            $this->delete($key);
        }
    }

    /**
     * Get current item count
     *
     * @return int
     */
    private function getCurrentItemCount(): int
    {
        return count($this->accessLog);
    }

    protected function getFromBackend(string $key): ?array
    {
        $file = $this->getPath($key);
        if (!file_exists($file)) {
            return null;
        }

        $this->accessLog[$key] = time();
        $this->saveMeta();

        $contents = file_get_contents($file);
        if ($contents === false) {
            return null;
        }

        $unserialized = unserialize($contents);
        return is_array($unserialized) ? $unserialized : null;
    }

    protected function setToBackend(string $key, array $data): bool
    {
        if (count($this->accessLog) >= $this->maxItems) {
            $this->evictLRU();
        }

        $this->accessLog[$key] = time();
        $this->saveMeta();

        return (bool)file_put_contents($this->getPath($key), serialize($data));
    }

    protected function deleteFromBackend(string $key): bool
    {
        $file = $this->getPath($key);
        return !file_exists($file) || unlink($file);
    }

    protected function clearBackend(): bool
    {
        $files = glob($this->directory . '/*.cache');
        if ($files === false) {
            return false;
        }

        array_map(unlink(...), $files);
        $this->accessLog = [];
        $this->saveMeta();

        return true;
    }

    /**
     *
     */
    public static function create (array $config = []): FileStorage
    {
        $storage = new FileStorage($config['path']);
        $storage->maxCapacity($config['max_items'] ?? 1000);
        return $storage;
    }
}