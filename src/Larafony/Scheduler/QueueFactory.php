<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Scheduler\Contracts\QueueContract;
use Larafony\Framework\Scheduler\Queue\DatabaseQueue;
use Larafony\Framework\Scheduler\Queue\RedisQueue;
use Larafony\Framework\Web\Application;
use RuntimeException;

class QueueFactory
{
    public static function make(): QueueContract
    {
        $config = Application::instance()->get(ConfigContract::class);
        $driver = $config->get('queue.default');

        return match ($driver) {
            'database' => new DatabaseQueue(),
            'redis' => new RedisQueue(),
            default => throw new RuntimeException("Unsupported queue driver: {$driver}")
        };
    }
}
