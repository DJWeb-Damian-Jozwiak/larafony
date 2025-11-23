<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\Environment\Parser;

use Larafony\Framework\Core\Support\Str;

/**
 * Parsuje wartość zmiennej (obsługuje cudzysłowy, escape sequences)
 */
class ValueParser
{
    /**
     * @return array{value: string, is_quoted: bool}
     */
    public function parse(string $value): array
    {
        $value = trim($value);
        $isQuoted = $this->isQuoted($value);
        if ($isQuoted) {
            $value = $this->unquote($value);
        }
        return [
            'value' => $value,
            'is_quoted' => $isQuoted,
        ];
    }

    private function isQuoted(string $value): bool
    {
        $value = trim($value);
        $firstChar = $value[0] ?? '';
        $lastChar = $value[strlen($value) - 1] ?? '';
        return Str::startsWith($value, ['"', "'"]) && Str::endsWith($value, ['"', "'"]) && $firstChar === $lastChar;
    }

    private function unquote(string $value): string
    {
        $quote = $value[0];
        $value = substr($value, 1, -1);

        // W double quotes, przetwórz escape sequences
        if ($quote === '"') {
            return $this->processEscapeSequences($value);
        }

        return $value;
    }

    private function processEscapeSequences(string $value): string
    {
        // Używamy preg_replace_callback dla poprawnej obsługi escape sequences
        return preg_replace_callback(
            '/\\\\(.)/',
            static function ($matches) {
                //@codeCoverageIgnoreStart
                return match ($matches[1]) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    '"' => '"',
                    '\\' => '\\',
                    default => '\\' . $matches[1], // Nieznane escape - pozostaw jak jest
                };
                //@codeCoverageIgnoreEnd
            },
            $value
        );
    }
}
