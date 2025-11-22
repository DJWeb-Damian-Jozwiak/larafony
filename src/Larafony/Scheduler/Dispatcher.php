<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler;

use DateTimeInterface;
use Larafony\Framework\Scheduler\Contracts\JobContract;
use Larafony\Framework\Scheduler\Contracts\QueueContract;

class Dispatcher
{
    public function __construct(private QueueContract $queue)
    {
    }

    /**
     * Dispatch a job to the queue immediately
     */
    public function dispatch(JobContract $job): string
    {
        return $this->queue->push($job);
    }

    /**
     * Dispatch a job to the queue at a specific time
     */
    public function dispatchAt(DateTimeInterface $delay, JobContract $job): string
    {
        return $this->queue->later($delay, $job);
    }

    /**
     * Dispatch a job after a number of seconds
     */
    public function dispatchAfter(int $seconds, JobContract $job): string
    {
        $delay = new \DateTime();
        $delay->modify("+{$seconds} seconds");

        return $this->queue->later($delay, $job);
    }

    /**
     * Dispatch multiple jobs
     *
     * @param array<JobContract> $jobs
     * @return array<string>
     */
    public function dispatchBatch(JobContract ...$jobs): array
    {
        $ids = [];

        foreach ($jobs as $job) {
            $ids[] = $this->dispatch($job);
        }

        return $ids;
    }

    /**
     * Get queue size
     */
    public function size(): int
    {
        return $this->queue->size();
    }

    /**
     * Delete a job from the queue
     */
    public function delete(string $id): void
    {
        $this->queue->delete($id);
    }
}
