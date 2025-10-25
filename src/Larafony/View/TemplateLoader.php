<?php

declare(strict_types=1);

namespace Larafony\Framework\View;

class TemplateLoader
{
    public function __construct(public private(set) string $template_path)
    {
    }

    public function load(string $template): string
    {
        // Convert dot notation to directory separator
        $template = str_replace('.', '/', $template);

        // Add .blade.php extension if not present
        if (! str_ends_with($template, '.blade.php')) {
            $template .= '.blade.php';
        }

        $path = $this->template_path . '/' . $template;
        if (! file_exists($path)) {
            throw new \RuntimeException("Template not found: {$path}");
        }
        return (string) file_get_contents($path);
    }
}
