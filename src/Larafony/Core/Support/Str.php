<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Support;

final class Str
{
    public static function isClassString(mixed $class): bool
    {
        return is_string($class) && class_exists($class);
    }
}
