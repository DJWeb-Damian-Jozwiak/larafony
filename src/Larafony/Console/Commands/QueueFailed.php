<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Scheduler\FailedJobRepository;

#[AsCommand(name: 'queue:failed')]
class QueueFailed extends Command
{
    public function __construct(
        OutputContract $output,
        ContainerContract $container,
        private readonly FailedJobRepository $repository
    ) {
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        $failedJobs = $this->repository->all();

        if (empty($failedJobs)) {
            $this->output->info('No failed jobs found');
            return 0;
        }

        $this->output->info(sprintf('Found %d failed job(s):', count($failedJobs)));

        foreach ($failedJobs as $job) {
            $msg = sprintf('UUID: %s | Queue: %s | Connection: %s', $job['uuid'], $job['queue'], $job['connection']);
            $this->output->warning($msg);
            $this->output->error(substr($job['exception'], 0, 100) . '...');
        }

        return 0;
    }
}
