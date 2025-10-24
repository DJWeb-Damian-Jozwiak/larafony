<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage;

class File
{
    public static function create(string $path, string $content = '', int $mode = 0777): void
    {
        file_put_contents($path, $content);
        chmod($path, $mode);
    }

    /**
     * @param string $path
     * @param string $cached_file
     *
     * @return bool
     *
     * @codeCoverageIgnore
     */
    public static function isCached(string $path, string $cached_file): bool
    {
        if (! file_exists($cached_file)) {
            return false;
        }
        return filemtime($cached_file) >= filemtime($path);
    }

    public static function ensureFileExists(string $path): void
    {
        set_error_handler(static function (): bool {
            return true;
        });
        $content = file_get_contents($path);
        restore_error_handler();
        if ($content === false) {
            throw new \RuntimeException("Unable to read file: {$path}");
        }
    }

    public static function unlink(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
    public static function ensureFileIsReadable(string $path): void
    {
        if (! is_readable($path)) {
            throw new \RuntimeException("Unable to read file: {$path}");
        }
    }
}
