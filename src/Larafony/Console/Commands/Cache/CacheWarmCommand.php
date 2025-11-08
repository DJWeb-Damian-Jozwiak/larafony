<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands\Cache;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;

#[AsCommand(name: 'cache:warm', description: 'Warm up the cache by preloading configured data')]
class CacheWarmCommand extends Command
{
    #[CommandOption(name: 'force', shortcut: 'f', description: 'Force warming even if keys already exist')]
    protected bool $force = false;

    #[CommandOption(name: 'batch', shortcut: 'b', value: '10', description: 'Batch size for warming (default: 10)')]
    protected int $batch = 10;

    public function run(): int
    {
        $this->output->info('Starting cache warming...');

        $cache = Cache::instance();
        $warmer = $cache->warmer();

        // Register warmers from configuration
        $this->registerWarmers($warmer);

        $count = $warmer->count();

        if ($count === 0) {
            $this->output->warning('No cache warmers registered. Configure warmers in your bootstrap or config files.');
            return 0;
        }

        $this->output->info("Found {$count} registered cache warmers");

        // Warm cache
        if ($this->batch > 1) {
            $result = $warmer->warmInBatches($this->batch, $this->force);
            $this->output->info("Processed in {$result['batches']} batches");
        } else {
            $result = $warmer->warmAll($this->force);
        }

        // Display results
        $this->output->success("Cache warming completed:");
        $this->output->writeln("  Total: {$result['total']}");
        $this->output->writeln("  Warmed: {$result['warmed']}");
        $this->output->writeln("  Skipped: {$result['skipped']}");

        if ($result['failed'] > 0) {
            $this->output->error("  Failed: {$result['failed']}");
            return 1;
        }

        return 0;
    }

    /**
     * Register warmers from application configuration
     *
     * @param \Larafony\Framework\Cache\CacheWarmer $warmer
     * @return void
     */
    private function registerWarmers($warmer): void
    {
        // Hook for applications to register their warmers
        // Applications can extend this command or use events
        $bootstrapFile = $this->container->getBinding('base_path') . '/bootstrap/cache-warmers.php';

        if (file_exists($bootstrapFile)) {
            require $bootstrapFile;
        }
    }
}
