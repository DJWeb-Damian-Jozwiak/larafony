<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Support;

final class Str
{
    public static function isClassString(mixed $class): bool
    {
        return is_string($class) && class_exists($class);
    }

    /**
     * @param array<int, string> $needle
     */
    public static function startsWith(string $haystack, array $needle): bool
    {
        return array_any($needle, static fn ($n) => str_starts_with($haystack, $n));
    }

    /**
     * @param array<int, string> $needle
     */
    public static function endsWith(string $haystack, array $needle): bool
    {
        return array_any($needle, static fn ($n) => str_ends_with($haystack, $n));
    }

    /**
     * Pluralize a word.
     *
     * @param string $word Word to pluralize
     * @param int $count Count (if 1, returns singular form)
     *
     * @return string Pluralized word
     */
    public static function pluralize(string $word, int $count = 2): string
    {
        return Pluralizer::pluralize($word, $count);
    }
}
