<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Partials\Frame;

use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\ErrorHandler\Renderers\Helpers\TraceFrameFetcher;
use Larafony\Framework\ErrorHandler\Renderers\Partials\CodeSnippetRenderer;
use Larafony\Framework\ErrorHandler\TraceCollection;

readonly class FrameSourceRenderer extends BaseFrameRenderer
{
    public function __construct(
        OutputContract $output,
        TraceFrameFetcher $frameFetcher,
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

        $this->output->info(sprintf(
            'Extended source for frame #%d (%s):',
            $frameIndex,
            basename($frame->file)
        ));

        $this->snippetRenderer->renderExtended($frame->file, $frame->line);
    }
}
