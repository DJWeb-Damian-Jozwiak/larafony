<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class EmptyDirective extends Directive
{
    public function compile(string $content): string
    {
        $content = $this->compilePattern(
            '/\@empty\s*\((.*?)\)/',
            $content,
            static fn ($matches) => "<?php if(empty({$matches[1]})): ?>"
        );

        return $this->compilePattern(
            '/\@endempty/',
            $content,
            static fn () => '<?php endif; ?>'
        );
    }
}
