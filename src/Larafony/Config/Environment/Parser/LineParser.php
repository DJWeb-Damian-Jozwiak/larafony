<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\Environment\Parser;

use Larafony\Framework\Config\Environment\Dto\EnvironmentVariable;
use Larafony\Framework\Config\Environment\Dto\LineType;
use Larafony\Framework\Config\Environment\Dto\ParsedLine;
use Larafony\Framework\Config\Environment\Exception\ParseError;

/**
 * Parsuje pojedynczą linię pliku .env
 */
class LineParser
{
    private const PATTERN = '/^([A-Z_][A-Z0-9_]*)\s*=\s*(.*)$/';
    private int $lineNumber = 0;

    public function __construct(
        private readonly ValueParser $valueParser = new ValueParser()
    ) {
    }

    public function reset(): void
    {
        $this->lineNumber = 0;
    }

    public function parse(string $line): ParsedLine
    {
        $original = $line;
        $line = trim($line);
        $this->lineNumber++;

        return match (true) {
            $line === '' => new ParsedLine($original, LineType::Empty, lineNumber: $this->lineNumber),
            str_starts_with($line, '#') => new ParsedLine($original, LineType::Comment, lineNumber: $this->lineNumber),
            ! preg_match(self::PATTERN, $line) => throw new ParseError(
                "Invalid syntax at line {$this->lineNumber}"
            ),
            default => $this->parseValue($line),
        };
    }

    private function parseValue(string $line): ParsedLine
    {
        $matches = [];
        preg_match(self::PATTERN, $line, $matches);
        [, $key, $rawValue] = $matches;
        $parsed = $this->valueParser->parse($rawValue);

        return new ParsedLine(
            raw: $line,
            type: LineType::Variable,
            variable: new EnvironmentVariable(
                key: $key,
                value: $parsed['value'],
                isQuoted: $parsed['is_quoted'],
                lineNumber: $this->lineNumber
            ),
            lineNumber: $this->lineNumber
        );
    }
}
