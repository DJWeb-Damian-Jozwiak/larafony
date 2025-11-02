<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers;

use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\ErrorHandler\Backtrace;
use Larafony\Framework\ErrorHandler\Renderers\Helpers\DebugSession;
use Larafony\Framework\ErrorHandler\Renderers\Partials\ConsoleCommandProcessor;
use Larafony\Framework\ErrorHandler\Renderers\Partials\ConsoleHeaderRenderer;
use Throwable;

readonly class ConsoleRenderer
{
    public function __construct(
        private OutputContract $output,
        private ConsoleHeaderRenderer $headerRenderer,
        private ConsoleCommandProcessor $commandProcessor
    ) {
    }

    public function render(Throwable $exception): int
    {
        $debugSession = new DebugSession(
            $this->output,
            $this->commandProcessor,
            new Backtrace()->generate($exception)
        );

        $this->headerRenderer->render($exception);
        return $debugSession->run();
    }
}
