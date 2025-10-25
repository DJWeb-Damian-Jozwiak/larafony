<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;

#[AsCommand(name: 'greet')]
class GreetCommand extends Command
{
    #[CommandArgument(name: 'name', description: 'The name of the person to greet')]
    protected string $name = 'World';

    #[CommandOption(name: 'greeting', description: 'The greeting to use')]
    protected string $greeting = 'Hello';

    #[CommandOption(name: 'shout', description: 'Shout the greeting')]
    protected bool $shout = false;

    public function run(): int
    {
        $message = "{$this->greeting}, {$this->name}!";

        if ($this->shout) {
            $message = strtoupper($message);
        }

        $this->output->success($message);

        return 0;
    }
}
