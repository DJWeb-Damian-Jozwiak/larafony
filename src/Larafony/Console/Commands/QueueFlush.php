<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Scheduler\FailedJobRepository;

#[AsCommand(name: 'queue:flush')]
class QueueFlush extends Command
{
    public function __construct(
        OutputContract $output,
        ContainerContract $container,
        private FailedJobRepository $repository
    ) {
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        $this->repository->flush();
        $this->output->success('All failed jobs have been flushed');

        return 0;
    }
}
