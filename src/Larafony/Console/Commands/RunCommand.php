<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Application;
use Larafony\Framework\Container\Contracts\ContainerContract;

class RunCommand
{
    public function __construct(
        private ?ContainerContract $container = null
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    public function run(string $command, array $arguments = []): int
    {
        // If container provided, get Application from it
        if ($this->container && $this->container->has(ContainerContract::class)) {
            $app = $this->container->get(ContainerContract::class);
            return $app->handle(['bin/larafony', $command, ...$arguments]);
        }

        // Fallback: use singleton instance (for standalone usage)
        $app = Application::instance();
        return $app->handle(['bin/larafony', $command, ...$arguments]);
    }
}
