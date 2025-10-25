<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class StackDirective extends Directive
{
    public function compile(string $content): string
    {
        $content = $this->compilePattern(
            '/\@push\([\'"](.*?)[\'"]\)(.*?)\@endpush/s',
            $content,
            static fn ($matches) => <<<PHP
            <?php
\$this->pushToStack('{$matches[1]}' , '{$matches[2]}'); ?>
PHP
        );

        // Handle @stack
        return $this->compilePattern(
            '/\@stack\([\'"](.*?)[\'"]\)/',
            $content,
            fn ($matches) => "<?php echo \$this->renderStack('{$matches[1]}'); ?>"
        );
    }
}
