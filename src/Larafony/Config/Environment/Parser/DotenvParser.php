<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\Environment\Parser;

use Larafony\Framework\Config\Environment\Dto\ParsedLine;
use Larafony\Framework\Config\Environment\Dto\ParserResult;

/**
 * Główny parser plików .env
 */
class DotenvParser implements ParserContract
{
    public function __construct(
        private readonly LineParser $lineParser = new LineParser(),
    ) {
    }

    public function parse(string $content): ParserResult
    {
        $this->lineParser->reset();

        $lines = str_replace("\r\n", "\n", $content)
                |> (static fn (string $content) => explode("\n", $content));

        $allParsedLines = array_map(fn (string $line) => $this->lineParser->parse($line), $lines)
            |> (static fn (array $lines) => array_filter($lines));

        $variables = array_filter($allParsedLines, static fn (ParsedLine $line) => $line->isVariable)
            |> (static fn (array $lines) => array_map(static fn (ParsedLine $line) => $line->variable, $lines))
            |> (static fn (array $variables) => array_filter($variables));

        return new ParserResult(
            variables: $variables,
            lines: $allParsedLines,
            totalLines: count($lines)
        );
    }
}
