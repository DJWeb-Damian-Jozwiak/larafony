<?php

declare(strict_types=1);

namespace Larafony\Framework\View;

use Larafony\Framework\Events\View\ViewRendered;
use Larafony\Framework\Events\View\ViewRendering;
use Larafony\Framework\Http\Response;
use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\Contracts\ViewContract;
use Larafony\Framework\Web\Application;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

class View extends Response implements ViewContract
{
    /**
     * @var array<string, mixed>
     */
    public protected(set) array $data = [];

    public function __construct(
        public protected(set) string $template,
        protected RendererContract $renderer
    ) {
        parent::__construct();
    }

    public function render(): ResponseInterface
    {
        $app = Application::instance();
        $eventDispatcher = $app->has(EventDispatcherInterface::class)
            ? $app->get(EventDispatcherInterface::class)
            : null;

        // Dispatch ViewRendering event before render
        $eventDispatcher?->dispatch(new ViewRendering($this->template, $this->data));

        $startTime = microtime(true);
        $content = $this->renderer->render($this->template, $this->data);
        $renderTime = (microtime(true) - $startTime) * 1000; // Convert to ms

        // Dispatch ViewRendered event after render
        $eventDispatcher?->dispatch(new ViewRendered(
            $this->template,
            $this->data,
            $content,
            $renderTime
        ));

        return $this->withContent($content)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function with(string $key, mixed $value): ViewContract
    {
        $this->data[$key] = $value;
        return $this;
    }
}
