<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Cache;

use Larafony\Framework\Core\Helpers\Directory;

readonly class CacheWriter
{
    public function __construct(private string $cacheFile)
    {
    }

    /**
     * @param array<int, \Larafony\Framework\Routing\Advanced\Route> $routes
     */
    public function put(string $path, array $routes): void
    {
        $cache = new CacheLoader($this->cacheFile)->loadCache();

        $cache[$path] = [
            'routes' => $routes,
            'mtime' => $this->getDirectoryMtime($path),
        ];

        $this->saveCache($cache);
    }

    private function getDirectoryMtime(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }
        $files = new Directory($path)->files;
        $files = array_filter($files, static fn (\SplFileInfo $file) => $file->getExtension() === 'php');
        $times = array_map(static fn (\SplFileInfo $file) => $file->getMTime(), $files);

        $maxMtime = filemtime($path);
        return max($maxMtime, ...$times);
    }

    /**
     * @param array<string, mixed> $cache
     */
    private function saveCache(array $cache): void
    {
        file_put_contents($this->cacheFile, serialize($cache), LOCK_EX);
    }
}
