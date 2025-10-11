<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Console\Kernel;

#[AsCommand(name: 'commands:cache-clear')]
class CommandsCacheClearCommand extends Command
{
    public function __construct(
        private readonly Kernel $kernel,
        public OutputContract $output
    ) {
        parent::__construct($output);
    }

    public function run(): int
    {
        $cacheFile = $this->getCachePath();

        if (! file_exists($cacheFile)) {
            $this->output->warning('No cache file found.');
            $this->output->info("Expected location: {$cacheFile}");
            return 0;
        }

        if (unlink($cacheFile)) {
            $this->output->success('Command cache cleared successfully!');
            $this->output->info("Deleted: {$cacheFile}");
            return 0;
        }

        $this->output->error('Failed to delete cache file.');
        return 1;
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
