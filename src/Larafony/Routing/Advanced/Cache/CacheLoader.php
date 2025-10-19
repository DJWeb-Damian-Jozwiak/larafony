<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Cache;

readonly class CacheLoader
{
    public function __construct(private string $cacheFile)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function loadCache(): ?array
    {
        if (! file_exists($this->cacheFile)) {
            return null;
        }

        $contents = file_get_contents($this->cacheFile);
        if ($contents === false) {
            return null;
        }

        $cache = unserialize($contents);
        return is_array($cache) ? $cache : null;
    }
}
