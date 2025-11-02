<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Helpers;

use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\ErrorHandler\Renderers\Partials\ConsoleCommandProcessor;
use Larafony\Framework\ErrorHandler\TraceCollection;

class DebugSession
{
    private bool $isRunning = true;

    public function __construct(
        private readonly OutputContract $output,
        private readonly ConsoleCommandProcessor $processor,
        private readonly TraceCollection $trace
    ) {
    }

    public function run(): int
    {
        while ($this->isRunning) {
            $this->processNextCommand();
        }
        return 0;
    }

    private function processNextCommand(): void
    {
        $command = $this->output->question("Debug mode: (type 'help' for available commands) ");

        if ($command === 'exit') {
            $this->isRunning = false;
            return;
        }

        $this->processor->process($command, $this->trace);
    }
}
