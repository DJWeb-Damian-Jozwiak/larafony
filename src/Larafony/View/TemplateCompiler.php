<?php

declare(strict_types=1);

namespace Larafony\Framework\View;

use Larafony\Framework\View\Contracts\DirectiveContract;

class TemplateCompiler
{
    /**
     * @var array<int, string> Map of compiled line number => original content
     */
    public private(set) array $lineMapping = [];

    /**
     * @param array<int, DirectiveContract> $directives
     */
    public function __construct(
        public private(set) array $directives = []
    ) {
    }

    public function compile(string $content): string
    {
        $this->lineMapping = [];
        $originalContent = $content;

        $content = $this->compileComments($content);

        $content = $this->compileEchos($content);

        array_walk(
            array: $this->directives,
            callback: static function (DirectiveContract $directive) use (&$content): void {
                $content = $directive->compile($content);
            }
        );

        // Extract and move use statements to the top
        $content = $this->extractUseStatements($content);

        // Build final mapping after all transformations
        $this->buildFinalMapping($originalContent, $content);

        return $content;
    }

    public function addDirective(DirectiveContract $directive): self
    {
        $this->directives[] = $directive;
        return $this;
    }

    private function compileComments(string $content): string
    {
        return preg_replace('/\{\{--(.*?)--\}\}/s', '<?php /* $1 */ ?>', $content) ?? '';
    }

    private function compileEchos(string $content): string
    {
        // Raw echo {!! $var !!}
        $content = preg_replace('/\{!!(.*?)!!\}/', '<?php echo $1; ?>', $content) ?? '';

        // Escaped echo {{ $var }}
        return preg_replace(
            '/\{\{(.*?)\}\}/',
            '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>',
            $content
        ) ?? '';
    }

    /**
     * Extract all use statements from PHP blocks and move them to the top of the file
     */
    private function extractUseStatements(string $content): string
    {
        $useStatements = [];

        // Find all use statements
        if (preg_match_all('/^\s*use\s+[^;]+;/m', $content, $matches)) {
            foreach ($matches[0] as $useStatement) {
                $trimmed = trim($useStatement);
                if (! in_array($trimmed, $useStatements, true)) {
                    $useStatements[] = $trimmed;
                }
                // Remove the use statement from its current location
                $content = str_replace($useStatement, '', $content);
            }
        }

        // If we found any use statements, prepend them to the content
        if ($useStatements !== []) {
            $useBlock = "<?php\n" . implode("\n", $useStatements) . "\n?>\n";
            $content = $useBlock . $content;
        }

        return $content;
    }

    private function buildFinalMapping(string $original, string $compiled): void
    {
        $originalLines = explode("\n", $original)
            |> (static fn ($lines) => array_filter($lines, static fn ($line) => trim($line) !== ''));
        $compiledLines = explode("\n", $compiled)
            |> (static fn ($lines) => array_filter($lines, static fn ($line) => str_contains($line, '<?php')));
        $this->lineMapping = [];
        foreach ($compiledLines as $compiledNum => $compiledLine) {
            $compliedClean = trim($compiledLine);
            $similarity = [];
            foreach ($originalLines as $originalNum => $originalLine) {
                $originalLine = trim($originalLine);
                $similarity[$originalNum] = similar_text($compliedClean, $originalLine);
            }
            $max = max($similarity);
            $matchingLine = array_search($max, $similarity);
            if (! in_array($matchingLine, $this->lineMapping, true)) {
                $this->lineMapping[$compiledNum] = $matchingLine;
            }
        }
    }
}
