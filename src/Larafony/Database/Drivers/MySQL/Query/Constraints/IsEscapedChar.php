<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Constraints;

class IsEscapedChar
{
    public static function check(string $char, string $next): bool
    {
        return ($char === "'" || $char === '?') && $next === $char;
    }
}
