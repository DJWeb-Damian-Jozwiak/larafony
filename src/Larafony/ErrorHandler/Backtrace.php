<?php

namespace Larafony\Framework\ErrorHandler;

use Throwable;

final class Backtrace
{
    public function generate(Throwable $exception): TraceCollection
    {
        return TraceCollection::fromThrowable($exception);
    }
}