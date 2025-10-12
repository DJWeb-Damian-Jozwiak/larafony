<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\Environment;

final class EnvReader
{
    public static function read(string $key, ?string $default = null): string|int|float|bool|null
    {
        return $_ENV[$key] ?? $default;
    }
}
