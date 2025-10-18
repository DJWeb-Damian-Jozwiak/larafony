<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Support\StrHelpers;

class RegularPluralize
{
    public static function execute(string $word): string
    {
        $rules = [
            // Words ending in 's', 'ss', 'sh', 'ch', 'x', 'z' -> add 'es'
            '/(s|ss|sh|ch|x|z)$/i' => static fn ($matches) => $matches[0] . 'es',
            // Words ending in consonant + 'y' -> replace 'y' with 'ies'
            '/([^aeiou])y$/i' => static fn ($matches) => $matches[1] . 'ies',
            // Words ending in 'f' or 'fe' -> replace with 'ves'
            '/(f|fe)$/i' => static fn ($matches) => 'ves',
            // Words ending in consonant + 'o' -> add 'es'
            '/([^aeiou])o$/i' => static fn ($matches) => $matches[0] . 'es',
        ];

        foreach ($rules as $pattern => $replacement) {
            $result = preg_replace_callback($pattern, $replacement, $word, 1, $count);
            if ($count > 0) {
                return $result;
            }
        }

        // Default: just add 's'
        return $word . 's';
    }
}
