<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Support\StrHelpers;

class PreserveCase
{
    /**
     * Preserve the case of the original word in the plural form.
     */
    public static function execute(string $original, string $plural): string
    {
        // All uppercase
        if (ctype_upper($original)) {
            return strtoupper($plural);
        }

        // First letter uppercase
        if (ctype_upper($original[0])) {
            return ucfirst($plural);
        }

        return $plural;
    }
}
