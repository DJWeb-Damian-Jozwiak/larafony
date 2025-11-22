<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Scheduler\QueueFactory;
use Larafony\Framework\Scheduler\Workers\QueueWorker;

#[AsCommand(name: 'queue:work')]
class QueueWork extends Command
{
    #[CommandOption(name: 'queue', description: 'The queue to work')]
    protected ?string $queue = null;

    #[CommandOption(name: 'once', description: 'Process a single job')]
    protected bool $once = false;

    #[CommandOption(name: 'max-jobs', description: 'Maximum number of jobs to process')]
    protected ?int $maxJobs = null;

    #[CommandOption(name: 'stop-when-empty', description: 'Stop when the queue is empty')]
    protected bool $stopWhenEmpty = false;

    public function __construct(
        OutputContract $output,
        ContainerContract $container
    ) {
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        $this->output->info('Starting queue worker...');

        $queue = QueueFactory::make();

        $iterations = match (true) {
            $this->once => 1,
            $this->maxJobs !== null => $this->maxJobs,
            default => 0
        };

        $worker = new QueueWorker($queue, $iterations);

        try {
            $worker->work();
            $this->output->success('Queue worker stopped successfully');
        } catch (\Throwable $e) {
            $this->output->error('Queue worker failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
