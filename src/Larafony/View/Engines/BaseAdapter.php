<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Engines;

use Larafony\Framework\View\Contracts\RendererContract;

abstract class BaseAdapter implements RendererContract
{
    public function clearCache(string $cache_path): void
    {
        if (is_dir($cache_path)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cache_path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                unlink($file->getRealPath());
            }
        }
    }
}
