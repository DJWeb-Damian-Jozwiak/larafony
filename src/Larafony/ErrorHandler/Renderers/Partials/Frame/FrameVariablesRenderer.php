<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Partials\Frame;

use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\ErrorHandler\Renderers\Helpers\TraceFrameFetcher;
use Larafony\Framework\ErrorHandler\Renderers\Helpers\VariableFormatter;
use Larafony\Framework\ErrorHandler\TraceCollection;
use Larafony\Framework\ErrorHandler\TraceFrame;

readonly class FrameVariablesRenderer extends BaseFrameRenderer
{
    public function __construct(
        OutputContract $output,
        TraceFrameFetcher $frameFetcher,
        private VariableFormatter $varFormatter
    ) {
        parent::__construct($output, $frameFetcher);
    }

    public function render(TraceCollection $trace, int $frameIndex): void
    {
        $frame = $this->getFrame($trace, $frameIndex);
        if (! $frame) { return;
        }

        $this->output->info("Local variables in frame #{$frameIndex}:");
        $this->renderVariables($frame);
    }

    private function renderVariables(TraceFrame $frame): void
    {
        $this->output->writeln('$this = ' . $this->varFormatter->format($frame->args[0] ?? null));
    }
}
