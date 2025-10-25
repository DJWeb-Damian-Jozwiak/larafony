<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class YieldDirective extends Directive
{
    public function compile(string $content): string
    {
        return $this->compilePattern(
            '/\@yield\([\'"](.*?)[\'"]\)/',
            $content,
            fn ($matches) => "<?php echo \$this->yield('{$matches[1]}'); ?>"
        );
    }
}
