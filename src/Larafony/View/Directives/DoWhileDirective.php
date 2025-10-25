<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class DoWhileDirective extends Directive
{
    public function compile(string $content): string
    {
        $content = $this->compilePattern(
            '/\@do/',
            $content,
            static fn () => '<?php do { ?>'
        );

        return $this->compilePattern(
            '/\@dowhile\s*\((.*?)\)/',
            $content,
            static fn ($matches) => "<?php } while({$matches[1]}); ?>"
        );
    }
}
