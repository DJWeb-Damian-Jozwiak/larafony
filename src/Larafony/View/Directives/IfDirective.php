<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class IfDirective extends Directive
{
    public function compile(string $content): string
    {
        $content = $this->compileIf($content);
        $content = $this->compileElseIf($content);
        $content = $this->compileElse($content);
        return $this->compileEndIf($content);
    }

    public function compileIf(string $content): string
    {
        return $this->compilePattern(
            '/\@if\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/',
            $content,
            static fn ($matches) => "<?php if({$matches[1]}): ?>"
        );
    }

    public function compileElseIf(string $content): string
    {
        return $this->compilePattern(
            '/\@elseif\s*\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/',
            $content,
            static fn ($matches) => "<?php elseif({$matches[1]}): ?>"
        );
    }

    public function compileElse(string $content): string
    {
        return $this->compilePattern(
            '/\@else/',
            $content,
            static fn () => '<?php else: ?>'
        );
    }

    public function compileEndIf(string $content): string
    {
        return $this->compilePattern(
            '/\@endif/',
            $content,
            static fn () => '<?php endif; ?>'
        );
    }
}
