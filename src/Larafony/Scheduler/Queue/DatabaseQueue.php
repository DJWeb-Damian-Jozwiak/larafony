<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler\Queue;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\FrozenClock;
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;
use Larafony\Framework\Database\ORM\Entities\Job as JobEntity;
use Larafony\Framework\Scheduler\Contracts\JobContract;
use Larafony\Framework\Scheduler\Contracts\QueueContract;

class DatabaseQueue implements QueueContract
{
    public function push(JobContract $job): string
    {
        $jobEntity = new JobEntity();
        $jobEntity->payload = serialize($job);
        $jobEntity->queue = 'default';
        $jobEntity->attempts = 0;
        $jobEntity->reserved_at = null;
        $jobEntity->available_at = ClockFactory::instance();
        $jobEntity->created_at = ClockFactory::instance();
        $jobEntity->save();

        return (string) $jobEntity->id;
    }

    public function later(\DateTimeInterface $delay, JobContract $job): string
    {
        $jobEntity = new JobEntity();
        $jobEntity->payload = serialize($job);
        $jobEntity->queue = 'default';
        $jobEntity->attempts = 0;
        $jobEntity->reserved_at = null;
        $jobEntity->available_at = new FrozenClock($delay);
        $jobEntity->created_at = ClockFactory::instance();
        $jobEntity->save();

        return (string) $jobEntity->id;
    }

    public function delete(string $id): void
    {
        $jobEntity = JobEntity::query()
            ->where('id', '=', $id)
            ->first();

        if ($jobEntity !== null) {
            $jobEntity->delete();
        }
    }

    public function size(): int
    {
        return JobEntity::query()
            ->where('available_at', '<=', ClockFactory::now())
            ->where('reserved_at', '=', null)
            ->count();
    }

    public function pop(): ?JobContract
    {
        /** @var ?JobEntity $jobEntity */
        $jobEntity = JobEntity::query()
            ->where('available_at', '<=', ClockFactory::now())
            ->where('reserved_at', '=', null)
            ->orderBy('available_at', OrderDirection::ASC)
            ->first();

        if ($jobEntity === null) {
            return null;
        }


        $jobEntity->delete();

        return unserialize($jobEntity->payload);
    }
}
