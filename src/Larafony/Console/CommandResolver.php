<?php

declare(strict_types=1);

namespace Larafony\Framework\Console;

use Larafony\Framework\Console\Input\Input;
use Larafony\Framework\Console\Resolvers\ArgumentResolver;
use Larafony\Framework\Console\Resolvers\OptionResolver;

class CommandResolver
{
    public function __construct(private Command $command, private Input $input)
    {
    }
    public function resolve(): void
    {
        new ArgumentResolver($this->command, $this->input)->resolveArguments();
        new OptionResolver($this->command, $this->input)->resolveOptions();
    }
}
