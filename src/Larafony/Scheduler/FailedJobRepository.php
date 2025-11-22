<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Core\Support\Str;
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;
use Larafony\Framework\Database\ORM\Entities\FailedJob;
use Larafony\Framework\Scheduler\Contracts\JobContract;

class FailedJobRepository
{
    /**
     * Log a failed job to the database
     */
    public function log(string $connection, string $queue, JobContract $job, \Throwable $exception): void
    {
        new FailedJob()->fill([
            'uuid' => Str::uuid(),
            'connection' => $connection,
            'queue' => $queue,
            'payload' => serialize($job),
            'exception' => $this->formatException($exception),
            'failed_at' => ClockFactory::instance(),
        ])->save();
    }

    /**
     * Get all failed jobs
     *
     * @return array<int, FailedJob>
     */
    public function all(): array
    {
        return FailedJob::query()->orderBy('failed_at', OrderDirection::DESC)->get();
    }

    /**
     * Find a failed job by UUID
     */
    public function find(string $uuid): ?FailedJob
    {
        /** @var ?FailedJob $model */
        $model = FailedJob::query()->where('uuid', '=', $uuid)->first();
        return $model;
    }

    /**
     * Delete a failed job by UUID
     */
    public function forget(string $uuid): bool
    {
        return (bool)$this->find($uuid)?->delete();
    }

    /**
     * Flush all failed jobs
     */
    public function flush(): void
    {
        FailedJob::query()->delete();
    }

    /**
     * Retry a failed job
     */
    public function retry(string $uuid): ?JobContract
    {
        $failedJob = $this->find($uuid);

        if ($failedJob === null) {
            return null;
        }

        $job = unserialize($failedJob->payload);

        if (!$job instanceof JobContract) {
            return null;
        }

        $this->forget($uuid);

        return $job;
    }

    /**
     * Prune failed jobs older than specified hours
     */
    public function prune(int $hours = 24): int
    {
        $date = ClockFactory::now()->modify("-{$hours} hours");

        $failedJobs = FailedJob::query()->where('failed_at', '<', $date)->get();

        $count = count($failedJobs);
        array_walk($failedJobs, fn (FailedJob $failedJob) => $failedJob->delete());
        return $count;
    }

    /**
     * Format exception for storage
     */
    private function formatException(\Throwable $exception): string
    {
        return sprintf(
            "%s: %s in %s:%d\nStack trace:\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
    }
}
