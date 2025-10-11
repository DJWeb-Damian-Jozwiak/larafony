<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Support;

final class FileToClassNameConverter
{
    public static function convert(\SplFileInfo $file, string $baseDirectory, string $namespace): string
    {
        $realPath = $file->getRealPath();
        $realBaseDir = realpath($baseDirectory);

        if ($realBaseDir === false || ! str_starts_with($realPath, $realBaseDir)) {
            return '';
        }

        $relativePath = substr($realPath, strlen($realBaseDir) + 1)
            |> (static fn (string $item) => str_replace(['\\', '.php'], ['/', ''], $item));

        if ($relativePath === '') {
            return '';
        }

        return $namespace . '\\' . $relativePath;
    }
}
