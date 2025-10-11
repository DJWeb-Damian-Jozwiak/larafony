<?php

declare(strict_types=1);

namespace Larafony\Framework\Console;

use Larafony\Framework\Console\Contracts\OutputContract;

abstract class Command
{
    public protected(set) OutputContract $output;

    public function __construct(OutputContract $output)
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
