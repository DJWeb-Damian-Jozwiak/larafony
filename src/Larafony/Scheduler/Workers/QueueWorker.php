<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler\Workers;

use Larafony\Framework\Scheduler\Contracts\QueueContract;
use Larafony\Framework\Scheduler\FailedJobRepository;

readonly class QueueWorker
{
    public function __construct(
        private QueueContract $queue,
        private int $iterations = 0,
        private ?FailedJobRepository $failedJobRepository = null
    ) {
    }

    public function work(): void
    {
        $iterations = 0;
        while (true) {
            $job = $this->queue->pop();

            if (! $this->shouldContinue($iterations)) {
                break;
            }
            $iterations++;
            if ($job === null) {
                sleep(1);
                continue;
            }

            try {
                $job->handle();
            } catch (\Throwable $e) {
                $this->handleFailedJob($job, $e);
            }
        }
    }

    private function shouldContinue(int $iterations): bool
    {
        return $this->iterations === 0 || $this->iterations > $iterations;
    }

    private function handleFailedJob(\Larafony\Framework\Scheduler\Contracts\JobContract $job, \Throwable $e): void
    {
        // First, let the job handle its own exception
        $job->handleException($e);

        // Then log to failed_jobs table if repository is available
        $this->failedJobRepository?->log(
            connection: 'default',
            queue: 'default',
            job: $job,
            exception: $e
        );
    }
}
