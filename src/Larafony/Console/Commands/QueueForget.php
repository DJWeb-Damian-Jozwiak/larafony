<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Scheduler\FailedJobRepository;

#[AsCommand(name: 'queue:forget')]
class QueueForget extends Command
{
    #[CommandArgument(name: 'uuid', description: 'UUID of failed job to forget')]
    protected string $uuid;

    public function __construct(
        OutputContract $output,
        ContainerContract $container,
        private FailedJobRepository $repository
    ) {
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        $result = $this->repository->forget($this->uuid);

        if (! $result) {
            $this->output->error("Failed job with UUID '{$this->uuid}' not found");
            return 1;
        }

        $this->output->success('Failed job forgotten successfully');

        return 0;
    }
}
