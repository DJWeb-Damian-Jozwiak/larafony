<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Partials;

use Larafony\Framework\Console\Contracts\OutputContract;

readonly class ConsoleHelpRenderer
{
    private const COMMANDS = [
        'trace' => 'Show full stack trace',
        'frame N' => 'Show details of frame N',
        'vars N' => 'Show local variables in frame N',
        'source N' => 'Show more source code for frame N',
        'env' => 'Show environment details',
        'help' => 'Show this help message',
        'exit' => 'Exit interactive debugger',
    ];

    public function __construct(
        private OutputContract $output
    ) {
    }

    public function render(): void
    {
        $this->output->info('Available commands:');
        foreach (self::COMMANDS as $command => $description) {
            $this->output->writeln(sprintf('  %-10s %s', $command, $description));
        }
    }
}
