<?php

declare(strict_types=1);

namespace Larafony\Framework\Console;

use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;

abstract class Command
{
    public protected(set) OutputContract $output;

    public function __construct(OutputContract $output, protected ContainerContract $container)
    {
        $this->output = $output;
    }

    /**
     * Execute the command
     *
     * @return int Exit code (0 for success, non-zero for error)
     */
    abstract public function run(): int;
}
