<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class WhileDirective extends Directive
{
    public function compile(string $content): string
    {
        $content = $this->compilePattern(
            '/\@while\s*\((.*?)\)/',
            $content,
            static fn ($matches) => "<?php while({$matches[1]}): ?>"
        );

        return $this->compilePattern(
            '/\@endwhile/',
            $content,
            static fn () => '<?php endwhile; ?>'
        );
    }
}
