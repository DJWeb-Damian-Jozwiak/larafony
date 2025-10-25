<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class IssetDirective extends Directive
{
    public function compile(string $content): string
    {
        $content = $this->compilePattern(
            '/\@isset\s*\((.*?)\)/',
            $content,
            static fn ($matches) => "<?php if(isset({$matches[1]})): ?>"
        );

        return $this->compilePattern(
            '/\@endisset/',
            $content,
            static fn () => '<?php endif; ?>'
        );
    }
}
