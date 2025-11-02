<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage;

class Directory
{
    public static function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
    public static function ensureDirectoryIsWritable(string $path): void
    {
        if (! is_writable($path)) {
            throw new \Exception("Directory {$path} is not writable");
        }
    }
}
