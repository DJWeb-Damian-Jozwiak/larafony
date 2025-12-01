<?php

declare(strict_types=1);

namespace Larafony\Framework\View;

class BladeSourceMapper
{
    /**
     * Resolve a compiled Blade file and line to original source
     *
     * @param string $compiledFile
     * @param int $compiledLine
     *
     * @return array{file: string, line: int}|null
     */
    public static function resolve(string $compiledFile, int $compiledLine): ?array
    {
        $mappingFile = $compiledFile . '.map.json';

        if (! file_exists($mappingFile)) {
            return null;
        }

        $mappingContent = file_get_contents($mappingFile);
        if ($mappingContent === false) {
            return null;
        }

        $mapping = json_decode($mappingContent, true, flags: JSON_THROW_ON_ERROR);

        // Get the original line number from mapping
        // $compiledLine is 1-indexed from backtrace, but map keys are 0-indexed
        $lineMapping = $mapping['lines'] ?? [];
        $originalLineNum = $lineMapping[$compiledLine - 1];

        // Get original file path
        $originalFile = $mapping['original'] ?? null;
        if ($originalFile === null || ! file_exists($originalFile)) {
            return null;
        }

        return [
            'file' => $originalFile,
            'line' => $originalLineNum + 1, // Convert from 0-indexed to 1-indexed
        ];
    }
}
