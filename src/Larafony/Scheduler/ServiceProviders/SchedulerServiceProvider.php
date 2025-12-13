<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler\ServiceProviders;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Scheduler\Contracts\QueueContract;
use Larafony\Framework\Scheduler\Contracts\ScheduleContract;
use Larafony\Framework\Scheduler\Dispatcher;
use Larafony\Framework\Scheduler\FailedJobRepository;
use Larafony\Framework\Scheduler\QueueFactory;
use Larafony\Framework\Scheduler\Schedule;

class SchedulerServiceProvider extends ServiceProvider
{
    /**
     * Register scheduler bindings
     *
     * @return array<string, class-string>
     */
    public function providers(): array
    {
        return [
            ScheduleContract::class => Schedule::class,
        ];
    }

    public function boot(ContainerContract $container): void
    {
        parent::boot($container);

        // Register Queue instance
        $queue = QueueFactory::make();
        $container->set(QueueContract::class, $queue);

        // Register Dispatcher with queue
        $dispatcher = new Dispatcher($queue);
        $container->set(Dispatcher::class, $dispatcher);

        // Register FailedJobRepository
        $failedJobRepository = new FailedJobRepository();
        $container->set(FailedJobRepository::class, $failedJobRepository);

        // Register Schedule instance
        $schedule = new Schedule();
        $container->set(ScheduleContract::class, $schedule);
        $container->set(Schedule::class, $schedule);

        // Load scheduled jobs from config if available
        $config = $container->get(ConfigContract::class);
        $scheduledJobs = $config->get('schedule', []);

        if ($scheduledJobs !== []) {
            $schedule->addFromConfig($scheduledJobs);
        }
    }
}
