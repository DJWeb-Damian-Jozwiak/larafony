<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands\Cache;

use Larafony\Framework\Cache\Cache;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Command;

#[AsCommand(name: 'cache:clear')]
class CacheClearCommand extends Command
{
    #[CommandArgument(
        name: 'tags',
        value: '',
        description: 'Optional: Comma-separated tags to clear (e.g., "users,posts")'
    )]
    protected string $tags = '';

    public function run(): int
    {
        $cache = $this->container->get(Cache::class);

        if ($this->tags !== '') {
            // Clear specific tags
            $tagArray = array_map('trim', explode(',', $this->tags));
            $this->output->info('Clearing cache for tags: ' . implode(', ', $tagArray));

            $success = $cache->tags($tagArray)->flush();

            if ($success) {
                $this->output->success('Tagged cache cleared successfully');
                return 0;
            }

            $this->output->error('Failed to clear tagged cache');
            return 1;
        }

        // Clear all cache
        $this->output->info('Clearing all cache...');

        if ($cache->clear()) {
            $this->output->success('Cache cleared successfully');
            return 0;
        }

        $this->output->error('Failed to clear cache');
        return 1;
    }
}
