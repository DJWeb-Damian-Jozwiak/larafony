<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Scheduler\QueueFactory;
use Larafony\Framework\Scheduler\Schedule;

#[AsCommand(name: 'schedule:run')]
class ScheduleRun extends Command
{
    public function __construct(
        OutputContract $output,
        ContainerContract $container,
        private ConfigContract $config
    ) {
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        $this->output->info('Running scheduled tasks...');

        $schedule = new Schedule();

        // Load schedule configuration
        $scheduleConfig = $this->config->get('schedule', []);

        $schedule->addFromConfig($scheduleConfig);

        $dueEvents = $schedule->dueEvents();

        $this->output->info(sprintf('Found %d scheduled task(s) to run', count($dueEvents)));

        $queue = QueueFactory::make();

        foreach ($dueEvents as $event) {
            try {
                $jobId = $queue->push($event->getJob());
                $jobClass = get_class($event->getJob());
                $this->output->success("Queued: {$jobClass} (ID: {$jobId})");
            } catch (\Throwable $e) {
                $this->output->error('Failed to queue job: ' . $e->getMessage());
            }
        }

        return 0;
    }
}
