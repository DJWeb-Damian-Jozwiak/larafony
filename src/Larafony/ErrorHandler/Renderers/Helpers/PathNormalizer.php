<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Helpers;

readonly class PathNormalizer
{
    public function getRelativePath(string $path): string
    {
        return str_replace(dirname(__DIR__, 4) . '/', '', $path);
    }
}
