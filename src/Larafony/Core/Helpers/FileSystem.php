<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Helpers;

final class FileSystem
{
    public static function createDirectoryIfMissing(string $path, int $permissions = 0775): void
    {
        if (! is_dir($path)) {
            mkdir($path, $permissions, true);
        }
    }

    public static function tryGetFileContent(string $path): string
    {
        set_error_handler(static function (): bool {
            return true;
        });
        $content = file_get_contents($path);
        restore_error_handler();

        return $content !== false ? $content : '';
    }
}
