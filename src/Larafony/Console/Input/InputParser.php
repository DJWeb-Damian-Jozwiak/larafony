<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Input;

class InputParser
{
    /**
     * Parses command line arguments into Input object
     *
     * @param array<int, string> $argv Raw command line arguments
     *
     * @return Input Parsed input object
     */
    public function parse(array $argv): Input
    {
        // Remove script name (first element)
        array_shift($argv);

        if (! $argv) {
            return new Input('');
        }
        // First argument is always the command name
        $command = array_shift($argv);

        $arguments = array_filter($argv, static fn ($arg) => ! str_starts_with($arg, '--'));
        $options = array_filter($argv, static fn ($arg) => str_starts_with($arg, '--'));
        return new Input($command, $arguments, $options);
    }
}
