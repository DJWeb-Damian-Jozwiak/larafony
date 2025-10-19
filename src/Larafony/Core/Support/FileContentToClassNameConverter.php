<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Support;

class FileContentToClassNameConverter
{
    public static function convert(string $filename): ?string
    {
        $content = file_get_contents($filename);
        if ($content === false) {
            return null;
        }
        $matches = [];
        preg_match('/namespace\s+([^;]+);/', $content, $matches);
        $namespace = $matches[1] ?? null;
        preg_match('/class\s+(\w+)/', $content, $matches);
        $className = $matches[1] ?? null;
        if ($namespace && $className) {
            return $namespace . '\\' . $className;
        }

        return $className;
    }
}
