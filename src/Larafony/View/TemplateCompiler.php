<?php

declare(strict_types=1);

namespace Larafony\Framework\View;

use Larafony\Framework\View\Contracts\DirectiveContract;

class TemplateCompiler
{
    /**
     * @param array<int, DirectiveContract> $directives
     */
    public function __construct(
        public private(set) array $directives = []
    ) {
    }

    public function compile(string $content): string
    {
        $content = $this->compileComments($content);

        $content = $this->compileEchos($content);

        array_walk(
            array: $this->directives,
            callback: static function (DirectiveContract $directive) use (&$content): void {
                $content = $directive->compile($content);
            }
        );

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
}
