<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class ForeachDirective extends Directive
{
    public function compile(string $content): string
    {
        $content = $this->compilePattern(
            '/\@foreach\s*\((.*?)\)/',
            $content,
            static fn ($matches) => "<?php foreach({$matches[1]}): ?>"
        );
        return $this->compilePattern(
            '/\@endforeach/',
            $content,
            static fn () => '<?php endforeach; ?>'
        );
    }
}
