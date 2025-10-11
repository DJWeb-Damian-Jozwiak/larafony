<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Input;

class ValueCaster
{
    private const array BOOLEAN_VALUES = [
        '1' => true,
        'on' => true,
        'true' => true,
        'yes' => true,
        '0' => false,
        'off' => false,
        'false' => false,
        'no' => false,
    ];

    public static function cast(string $value): bool|int|float|string
    {
        if (isset(self::BOOLEAN_VALUES[$value])) {
            return self::BOOLEAN_VALUES[$value];
        }
        return match (true) {
            ctype_digit($value) => (int) $value,
            is_numeric($value) => (float) $value,
            default => $value,
        };
    }
}
