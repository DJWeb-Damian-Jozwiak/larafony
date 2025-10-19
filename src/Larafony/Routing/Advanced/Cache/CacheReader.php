<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Cache;

use Larafony\Framework\Core\Helpers\Directory;
use Larafony\Framework\Routing\Advanced\Route;

readonly class CacheReader
{
    public function __construct(private string $cacheFile)
    {
    }

    /**
     * @return array<int, Route>|null
     */
    public function get(string $path): ?array
    {
        $cache = new CacheLoader($this->cacheFile)->loadCache();
        if ($cache === null || ! isset($cache[$path])) {
            return null;
        }

        if ($this->isStale($path, $cache[$path]['mtime'])) {
            return null;
        }

        return $cache[$path]['routes'];
    }

    private function isStale(string $path, int $cachedMtime): bool
    {
        return $this->getDirectoryMtime($path) > $cachedMtime;
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
}
