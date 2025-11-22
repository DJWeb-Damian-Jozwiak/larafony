<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler\Contracts;

interface QueueContract
{
    public function push(JobContract $job): string;
    public function later(\DateTimeInterface $delay, JobContract $job): string;
    public function delete(string $id): void;
    public function size(): int;
    public function pop(): ?JobContract;
}
