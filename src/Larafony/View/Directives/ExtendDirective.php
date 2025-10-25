<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class ExtendDirective extends Directive
{
    public function compile(string $content): string
    {
        return $this->compilePattern(
            '/\@extends\([\'"](.*?)[\'"]\)/',
            $content,
            fn ($matches) => "<?php \$this->extend('{$matches[1]}'); ?>"
        );
    }
}
