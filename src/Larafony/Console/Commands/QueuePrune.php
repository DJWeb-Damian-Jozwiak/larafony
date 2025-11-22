<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Scheduler\FailedJobRepository;

#[AsCommand(name: 'queue:prune')]
class QueuePrune extends Command
{
    #[CommandOption(name: 'hours', description: 'Number of hours to retain failed jobs')]
    protected int $hours = 24;

    public function __construct(
        OutputContract $output,
        ContainerContract $container,
        private FailedJobRepository $repository
    ) {
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        $count = $this->repository->prune($this->hours);

        $this->output->success(
            sprintf('Pruned %d failed job(s) older than %d hours', $count, $this->hours)
        );

        return 0;
    }
}
