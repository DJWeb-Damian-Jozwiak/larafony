<?php

declare(strict_types=1);

namespace Larafony\Framework\View;

use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\Contracts\ViewContract;

class ViewManager
{
    public function __construct(
        public protected(set) RendererContract $renderer
    ) {
    }

    /**
     * @param string $template
     * @param array<string, mixed> $data
     *
     * @return ViewContract
     */
    public function make(string $template, array $data = []): ViewContract
    {
        $view = new View($template, $this->renderer);
        foreach ($data as $key => $value) {
            $view->with($key, $value);
        }
        return $view;
    }

    public function withRenderer(RendererContract $renderer): self
    {
        return clone($this, ['renderer' => $renderer]);
    }
}
