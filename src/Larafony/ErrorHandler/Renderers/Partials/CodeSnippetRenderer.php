<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Partials;

use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\ErrorHandler\CodeSnippet;

readonly class CodeSnippetRenderer
{
    public function __construct(
        private OutputContract $output
    ) {
    }

    public function render(CodeSnippet $snippet): void
    {
        $this->output->writeln('');
        $this->output->info('Source:');
        $this->renderLines($snippet->lines, $snippet->errorLine);
    }

    public function renderExtended(string $file, int $errorLine, int $context = 20): void
    {
        $extendedSnippet = new CodeSnippet($file, $errorLine, $context);
        $this->renderLines($extendedSnippet->lines, $errorLine);
    }

    private function renderLines(array $lines, int $errorLine): void
    {
        foreach ($lines as $lineNum => $line) {
            $prefix = $lineNum === $errorLine ? '> ' : '  ';
            $this->output->writeln(sprintf(
                '%s%4d| %s',
                $prefix,
                $lineNum,
                $line
            ));
        }
    }
}
