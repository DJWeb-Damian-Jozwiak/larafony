<?php

declare(strict_types=1);

namespace Larafony\Framework\View\Directives;

class ViteDirective extends Directive
{
    public function compile(string $content): string
    {
        return $this->compilePattern(
            '/\@vite\s*\((.*?)\)/',
            $content,
            static function ($matches) {
                $assets = trim($matches[1]);
                return "<?php echo (
new \Larafony\Framework\View\Inertia\Vite(\Larafony\Framework\Web\Application::instance()))->render({$assets}); ?>";
            }
        );
    }
}
