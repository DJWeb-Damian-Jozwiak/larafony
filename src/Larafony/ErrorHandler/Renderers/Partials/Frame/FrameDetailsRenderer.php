<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Partials\Frame;

use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\ErrorHandler\Renderers\Helpers\TraceFrameFetcher;
use Larafony\Framework\ErrorHandler\Renderers\Helpers\VariableFormatter;
use Larafony\Framework\ErrorHandler\Renderers\Partials\CodeSnippetRenderer;
use Larafony\Framework\ErrorHandler\TraceCollection;
use Larafony\Framework\ErrorHandler\TraceFrame;

readonly class FrameDetailsRenderer extends BaseFrameRenderer
{
    public function __construct(
        OutputContract $output,
        TraceFrameFetcher $frameFetcher,
        private VariableFormatter $varFormatter,
        private CodeSnippetRenderer $snippetRenderer
    ) {
        parent::__construct($output, $frameFetcher);
    }

    public function render(TraceCollection $trace, int $frameIndex): void
    {
        $frame = $this->getFrame($trace, $frameIndex);
        if (! $frame) {
            return;
        }

        $this->renderHeader($frame, $frameIndex);
        $this->renderCall($frame);
        $this->snippetRenderer->render($frame->snippet);
    }

    private function renderHeader(TraceFrame $frame, int $frameIndex): void
    {
        $this->output->writeln('');
        $this->output->info("Frame #{$frameIndex} Details:");
        $this->output->writeln(sprintf('Location: %s:%d', $frame->file, $frame->line));
    }

    private function renderCall(TraceFrame $frame): void
    {
        $this->output->writeln(sprintf(
            'Call: %s%s(%s)',
            $frame->class ? $frame->class . '::' : '',
            $frame->function,
            $this->varFormatter->formatArgs($frame->args)
        ));
    }
}
