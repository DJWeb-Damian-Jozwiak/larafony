<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler\Workers;

use Larafony\Framework\Scheduler\Contracts\QueueContract;
use Larafony\Framework\Scheduler\Schedule;

readonly class ScheduleWorker
{
    public function __construct(
        private Schedule $schedule,
        private QueueContract $queue,
        private int $iterations = 0
    ) {
    }

    public function work(): void
    {
        $iterations = 0;
        while (true) {
            if (! $this->shouldContinue($iterations)) {
                break;
            }

            foreach ($this->schedule->dueEvents() as $event) {
                $this->queue->push($event->getJob());
            }

            sleep(60); // Check every minute
        }
    }

    private function shouldContinue(int $iterations): bool
    {
        return $this->iterations === 0 || $this->iterations > $iterations;
    }
}
