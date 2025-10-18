<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Constraints;

class IsPlaceholder
{
    public static function check(string $char, bool $inString): bool
    {
        return $char === '?' && ! $inString;
    }
}
