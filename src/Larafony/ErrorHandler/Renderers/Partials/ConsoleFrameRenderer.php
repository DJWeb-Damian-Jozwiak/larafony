<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Partials;

use Larafony\Framework\ErrorHandler\Renderers\Partials\Frame\FrameDetailsRenderer;
use Larafony\Framework\ErrorHandler\Renderers\Partials\Frame\FrameSourceRenderer;
use Larafony\Framework\ErrorHandler\Renderers\Partials\Frame\FrameVariablesRenderer;
use Larafony\Framework\ErrorHandler\TraceCollection;

readonly class ConsoleFrameRenderer
{
    public function __construct(
        private FrameDetailsRenderer $detailsRenderer,
        private FrameVariablesRenderer $variablesRenderer,
        private FrameSourceRenderer $sourceRenderer
    ) {
    }

    public function renderFrame(TraceCollection $trace, int $frameIndex): void
    {
        $this->detailsRenderer->render($trace, $frameIndex);
    }

    public function renderVariables(TraceCollection $trace, int $frameIndex): void
    {
        $this->variablesRenderer->render($trace, $frameIndex);
    }

    public function renderSource(TraceCollection $trace, int $frameIndex): void
    {
        $this->sourceRenderer->render($trace, $frameIndex);
    }
}
