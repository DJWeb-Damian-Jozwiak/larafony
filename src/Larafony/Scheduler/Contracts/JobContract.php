<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler\Contracts;

interface JobContract
{
    public function handle(): void;
    public function handleException(\Throwable $e): void;
}
