<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Constraints;

class IsStringBoundary
{
    public static function check(string $char, string $next): bool
    {
        return $char === "'" && $next !== "'";
    }
}
