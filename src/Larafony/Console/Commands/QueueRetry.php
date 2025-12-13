<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Scheduler\Dispatcher;
use Larafony\Framework\Scheduler\FailedJobRepository;

#[AsCommand(name: 'queue:retry')]
class QueueRetry extends Command
{
    #[CommandArgument(name: 'uuid', description: 'UUID of failed job to retry (or "all" for all failed jobs)')]
    protected string $uuid;

    public function __construct(
        OutputContract $output,
        ContainerContract $container,
        private FailedJobRepository $repository,
        private Dispatcher $dispatcher
    ) {
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        if ($this->uuid === 'all') {
            return $this->retryAll();
        }

        return $this->retrySingle($this->uuid);
    }

    private function retrySingle(string $uuid): int
    {
        $job = $this->repository->retry($uuid);

        if ($job === null) {
            $this->output->error("Failed job with UUID '{$uuid}' not found");
            return 1;
        }

        $jobId = $this->dispatcher->dispatch($job);
        $this->output->success("Job retried successfully (new ID: {$jobId})");

        return 0;
    }

    private function retryAll(): int
    {
        $failedJobs = $this->repository->all();

        if ($failedJobs === []) {
            $this->output->info('No failed jobs to retry');
            return 0;
        }

        $count = 0;
        foreach ($failedJobs as $failedJob) {
            $job = $this->repository->retry($failedJob->uuid);
            if ($job !== null) {
                $this->dispatcher->dispatch($job);
                $count++;
            }
        }

        $this->output->success("Retried {$count} failed job(s)");

        return 0;
    }
}
