<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Cache;

use Larafony\Framework\Routing\Advanced\Route;

readonly class RouteCache
{
    private string $cacheFile;
    private CacheReader $reader;
    private CacheWriter $writer;

    public function __construct(?string $cacheDir = null)
    {
        $cacheDir ??= sys_get_temp_dir() . '/larafony-routes';
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $this->cacheFile = $cacheDir . '/routes.cache';

        $this->reader = new CacheReader($this->cacheFile);
        $this->writer = new CacheWriter($this->cacheFile);
    }

    /**
     * @return array<int, Route>|null
     */
    public function get(string $path): ?array
    {
        return $this->reader->get($path);
    }

    /**
     * @param array<int, Route> $routes
     */
    public function put(string $path, array $routes): void
    {
        $this->writer->put($path, $routes);
    }

    public function clear(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
}
