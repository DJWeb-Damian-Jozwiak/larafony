<?php

declare(strict_types=1);

namespace Larafony\Framework\Console;

use Larafony\Framework\Console\Exceptions\CommandNotFoundError;
use Larafony\Framework\Console\Input\InputParser;
use Larafony\Framework\Container\Contracts\ContainerContract;

final readonly class Kernel
{
    /**
     * @var array<string, string> $commandPaths
     */
    private array $commandPaths;
    public function __construct(
        private string $rootPath,
        private CommandCache $commandCache,
        private CommandRegistry $commandRegistry,
        private ContainerContract $container,
    ) {
        $this->commandPaths = [
            $this->getCommandsDirectory() => 'App\\Console\\Commands',
        ];
    }

    /**
     * @param array<int, string> $argv
     *
     * @return int
     */
    public function handle(array $argv): int
    {
        $input = new InputParser()->parse($argv);

        // Start with framework commands from registry (registered in service providers)
        $commands = $this->commandRegistry->commands;

        // Add application commands from cache (if exists) or discovery
        if ($this->commandCache->load($this->getCachePath())) {
            $commands = [...$commands, ...$this->commandCache->commands];
        } else {
            foreach ($this->commandPaths as $path => $namespace) {
                $discovery = new CommandDiscovery();
                $discovery->discover($path, $namespace);
                $commands = [...$commands, ...$discovery->commands];
            }
        }

        $command = $commands[$input->command] ?? throw new CommandNotFoundError($input->command);
        $command = $this->container->get($command);
        new CommandResolver($command, $input)->resolve();
        return $command->run();
    }

    public function getCommandsDirectory(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->rootPath,
            'src',
            'Console',
            'Commands',
        ]);
    }

    private function getCachePath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->rootPath,
            'storage',
            'cache',
            'commands.php',
        ]);
    }
}
