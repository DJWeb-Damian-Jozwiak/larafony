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
     * Get the class basename from a fully qualified class name.
     *
     * @param class-string<object> $class The class name or object
     *
     * @return string The class basename
     */
    public static function classBasename(string $class): string
    {
        return explode('\\', $class) |> array_last(...);
    }

    public static function snake(string $value): string
    {
        // If already lowercase with underscores, return as-is
        $replace = ['_', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        if (str_replace($replace, '', $value) |> ctype_lower(...)) {
            return $value;
        }

        // Insert underscores before uppercase letters and convert to lowercase
        $value = preg_replace('/([a-z\d])([A-Z])/', '$1_$2', $value);
        return preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $value) |> strtolower(...);
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
