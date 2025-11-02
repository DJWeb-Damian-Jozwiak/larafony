<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Partials\Frame;

use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\ErrorHandler\Renderers\Helpers\TraceFrameFetcher;
use Larafony\Framework\ErrorHandler\TraceCollection;
use Larafony\Framework\ErrorHandler\TraceFrame;

abstract readonly class BaseFrameRenderer
{
    public function __construct(
        protected OutputContract $output,
        private TraceFrameFetcher $frameFetcher
    ) {
    }

    protected function getFrame(TraceCollection $trace, int $frameIndex): ?TraceFrame
    {
        return $this->frameFetcher->fetch($trace, $frameIndex);
    }
}
