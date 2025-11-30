<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Attributes\CommandOption;

#[AsCommand(name: 'test:command')]
class TestCommand extends \Larafony\Framework\Console\Command
{
    #[CommandArgument(name: 'sample_argument', value: 1, description: 'Sample argument description')]
    public int $sample_argument = 1;

    #[CommandOption(name: 'sample_option', description: 'Sample option description')]
    public string $sample_option = 'default';

    #[CommandOption(name: '?sample_optional_option', description: 'Sample optional option description')]
    public string $sample_optional_option;
    public function run(): int
    {
        $this->output->writeln('Hello World!');
        return 0;
    }
}