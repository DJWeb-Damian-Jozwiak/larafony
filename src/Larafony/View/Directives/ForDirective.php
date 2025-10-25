<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class ForDirective extends Directive
{
    public function compile(string $content): string
    {
        $content = $this->compilePattern(
            '/\@for\s*\((.*?)\)/',
            $content,
            static fn ($matches) => "<?php for({$matches[1]}): ?>"
        );

        return $this->compilePattern(
            '/\@endfor/',
            $content,
            static fn () => '<?php endfor; ?>'
        );
    }
}
