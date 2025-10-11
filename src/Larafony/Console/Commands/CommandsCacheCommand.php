<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\CommandCache;
use Larafony\Framework\Console\CommandDiscovery;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Console\Kernel;

#[AsCommand(name: 'commands:cache')]
class CommandsCacheCommand extends Command
{
    public function __construct(
        private readonly Kernel $kernel,
        public OutputContract $output
    ) {
        parent::__construct($output);
    }

    public function run(): int
    {
        $this->output->info('Discovering commands...');

        // Create new discovery instance
        $discovery = new CommandDiscovery();

        // Discover all commands from configured paths
        $commandsDir = $this->kernel->getCommandsDirectory();
        $discovery->discover($commandsDir, 'App\\Console\\Commands');

        if (count($discovery->commands) === 0) {
            $this->output->warning('No commands found to cache.');
            return 0;
        }

        new CommandCache()->withCommands($discovery->commands)->save($this->getCachePath());

        $count = count($discovery->commands);
        $this->output->success("Cached {$count} command(s)");
        $this->output->info("Location: {$this->getCachePath()}");

        return 0;
    }

    private function getCachePath(): string
    {
        $basePath = $this->kernel->getCommandsDirectory();
        $basePath = dirname($basePath, 3); // Go up to root (src/Console/Commands -> root)

        return implode(DIRECTORY_SEPARATOR, [
            $basePath,
            'storage',
            'cache',
            'commands.php',
        ]);
    }
}
