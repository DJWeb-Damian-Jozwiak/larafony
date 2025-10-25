<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class UnlessDirective extends Directive
{
    public function compile(string $content): string
    {
        $content = $this->compilePattern(
            '/\@unless\s*\((.*?)\)/',
            $content,
            static fn ($matches) => "<?php if(! ({$matches[1]})): ?>"
        );

        return $this->compilePattern(
            '/\@endunless/',
            $content,
            static fn () => '<?php endif; ?>'
        );
    }
}
