<?php

declare(strict_types=1);

namespace Larafony\Framework\Console;

use Larafony\Framework\Console\Exceptions\CommandNotFoundError;

class CommandRegistry
{
    /**
     * @var array<string, class-string<Command>>
     */
    public private(set) array $commands = [];

    /**
     * Register a command manually
     *
     * @param string $name Command name (e.g., 'database:seed')
     * @param class-string<Command> $commandClass
     */
    public function register(string $name, string $commandClass): void
    {
        $this->commands[$name] = $commandClass;
    }

    /**
     * Get command class by name
     *
     * @param string $name
     *
     * @return class-string<Command>
     *
     * @throws CommandNotFoundError
     */
    public function get(string $name): string
    {
        if (! $this->has($name)) {
            throw new CommandNotFoundError("Command '{$name}' not found.");
        }

        return $this->commands[$name];
    }

    /**
     * Check if command exists
     */
    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * Get all registered commands
     *
     * @return array<string, class-string<Command>>
     */
    public function all(): array
    {
        return $this->commands;
    }

    /**
     * Discover commands from directory
     *
     * @param string $directory Directory to scan for commands
     * @param string $namespace Base namespace for commands
     */
    public function discover(string $directory, string $namespace): void
    {
        $discovery = new CommandDiscovery($this->commands);
        $discovery->discover($directory, $namespace);
        $this->commands = $discovery->commands;
    }
}
