<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class SectionDirective extends Directive
{
    public function compile(string $content): string
    {
        $content = $this->compilePattern(
            '/\@section\([\'"](.*?)[\'"]\)/',
            $content,
            fn ($matches) => "<?php \$this->section('{$matches[1]}'); ?>"
        );

        return $this->compilePattern(
            '/\@endsection/',
            $content,
            static fn () => '<?php $this->endSection(); ?>'
        );
    }
}
