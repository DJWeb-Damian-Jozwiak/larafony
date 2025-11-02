<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Helpers;

use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\ErrorHandler\TraceCollection;
use Larafony\Framework\ErrorHandler\TraceFrame;

readonly class TraceFrameFetcher
{
    public function __construct(
        private OutputContract $output
    ) {
    }

    public function fetch(TraceCollection $trace, int $frameIndex): ?TraceFrame
    {
        $frame = $trace->frames[$frameIndex] ?? null;

        if (! $frame) {
            $this->output->error("Frame {$frameIndex} not found");
            return null;
        }

        return $frame;
    }
}
